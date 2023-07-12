<?php

namespace App\Http\Controllers;

use App\Http\Controllers\APIController;
use App\Http\Controllers\CollectionController;
use App\Models\Collection;
use App\Models\Config;
use App\Models\Hirer;
use App\Models\User;
use Illuminate\Http\Request;
use Mail;

class EmailController extends APIController
{
    public function replaceValues($template, $replaces = [])
    {
        if (empty($replaces)) {
            return $template;
        }
        if (is_string($replaces)) {
            $replaces = json_decode($replaces, true);
        }
        /** Pattern is set to find something like @hirer-type.typeid and replace */
        $pattern = '/@(\w+)\.([\w-]+)\.([\w]+)/';
        $collectionController = new CollectionController();

        $formatted = [];
        foreach ($replaces as $key => $value) {
            if (preg_match_all($pattern, $value, $matches)) {
                foreach ($matches[0] as $match) {
                    $check = explode('.', str_replace('@', '', $match));

                    if ($check[0] === 'hirer') {
                        $value = Hirer::where('id', $check[1])->pluck($check[2] ?? 'name')->first();
                    } else {
                        $item = $collectionController->loadReference($check[0], $check[1])->first();
                        if (isset($item->{$check[2]})) {
                            $value = $item->{$check[2]};
                        }
                    }
                }
            }

            /** Set up to replace placeholders like $$key$$, {{key}}, {key} and ##key## */
            $formatted['$$' . $key . '$$'] = $value;
            $formatted['{{' . $key . '}}'] = $value;
            $formatted['{' . $key . '}'] = $value;
            $formatted['##' . $key . '##'] = $value;
        }

        return strtr($template, $formatted);
    }

    /**
     * Send an email from the request
     */
    public function sendEmail(Request $request)
    {
        /** Load the template reference, replace strings and to addresses */
        $templateReference = $request->template ?? 'user';
        $isSupport = ($request->template ?? '') === 'support';
        $replace = $request->has('replace') ? is_array($request->replace) ? $request->replace : json_decode($request->replace, true) : [];
        $to = $request->has('to') ? is_array($request->to) ? $request->to : json_decode($request->to, true) : [];

        $defaultTestEmailAddress = 'todds@imscomply.com.au';

        /** Get the settings from config to determine if email are live and a testing email adress if not */
        $sendLiveEmail = false;
        $testEmailAddress = $defaultTestEmailAddress;
        $settings = Config::where('code', 'live_email')
            ->orWhere('code', 'test_email')
            ->orWhere('code', 'name')
            ->get()
            ->toArray();

        $fromName = 'noreply';
        foreach ($settings as $setting) {
            if ($setting['code'] == 'live_email' && !empty($setting['value'])) {
                $sendLiveEmail = $setting['value'];
            }
            if ($setting['code'] == 'test_email' && !empty($setting['value'])) {
                $testEmailAddress = $setting['value'];
            }

            if ($setting['code'] == 'name' && !empty($setting['value'])) {
                $fromName = $setting['value'] . ' - noreply';
            }

        }

        /** Get the email templates and find where the reference matches the $templateReference */
        $template = Collection::where('slug', 'email-template')
            ->get()
            ->filter(function ($item) use ($templateReference) {
                $reference = $item->fields->filter(function ($item) {
                    return $item->reference == 'reference';
                })->first();
                return $reference->value == $templateReference;
            })->map([$this, 'mapFieldsToValues'])
            ->first();

        /** If no template, set from the request values sent */
        if (empty($template)) {
            $template = [
                'body' => $request->body ?? '',
                'subject' => $request->subject ?? 'New email',
                'has_signature' => $request->has_signature ?? false,
                'signature' => $request->signature ?? false,
            ];
        }

        /** Format the email body and replace placeholders */
        $html = $this->replaceValues(
            substr($template['body'], 0, 1) == '<'
            ? $template['body']
            : nl2br($template['body']),
            $replace
        );

        /** Format the email subject and replace placeholders */
        $template['subject'] = $this->replaceValues(
            $template['subject'],
            $replace
        );

        /** Check if the template has as signature and append to html */
        if (!empty((boolean) $template['has_signature']) && !empty($template['signature'])) {
            $html .= '<br /><br />' . $this->replaceValues($template['signature'], $replace);
        }

        /** If the request is to send the email to a particular role, fetch the users with that role id */
        if (!empty($request->role)) {
            $users = User::select(['users.name', 'users.email'])
                ->distinct('users.email')
                ->join('role', function ($join) use ($request) {
                    $join->on('role.id', '=', 'users.role_id')
                        ->on('role_id', '=', \DB::raw("'" . $request->role . "'"));
                })->get()->toArray();

            /** TODO: Need to link to the users email templates */

            $to = $users;
        }

        /** If there are to addresses, use them, otherwise use the testing email */
        $to = !empty($to) && is_array($to) ? $to : [['email' => $testEmailAddress, 'name' => 'IMS Testing']];

        /** If not sending live emails and it's not the support form, output which email address it would have been sent to */
        if (empty($sendLiveEmail) && !$isSupport) {
            if (!empty($to[0])) {
                $html = '<br />-- Testing Email -- Would have been sent to: ' . implode(', ', array_map(function ($item) {return $item['name'] . ' ' . $item['email'];}, $to)) . '<br /><br />' . $html;
            }
            $to = [['email' => $testEmailAddress, 'name' => 'IMS Testing']];
        }

        /** Set the from address */
        $from = ['email' => 'ims@imscomply.com.au', 'name' => $fromName];

        /** Loop through each of the to addresses and send an individual email */
        foreach ($to as $item) {
            if (!isset($item['email'])) {
                continue;
            }

            Mail::send([], [], function ($message) use ($template, $request, $html, $item, $from) {
                $message->from($from['email'], $from['name']);

                /** Attach the file if it is sent (currently only single files) */
                if ($request->has('file')) {
                    $file = $request->file;
                    $message->attach($file->getRealPath(), [
                        'as' => $file->getClientOriginalName(),
                        'mime' => $file->getMimeType()]
                    );
                }

                /** Set the to address and name */
                $message->to($item['email'], $item['name'] ?? 'No name');
                /** Add BCC */
                $message->bcc($from['email'], $from['name']);
                /** Set the subject */
                $message->subject($template['subject']);
                /** Set the html body */
                $message->setBody('<html><body>' . $html . '</body></html>', 'text/html');
            });
        }

        /** TODO: Handle if email failed to send */
        return $this->successResponse(['data' => 'Sent email!']);
    }
}
