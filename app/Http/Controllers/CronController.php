<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CronController extends Controller
{
    public function __construct()
    {
        if (!request()->has('site-name')) {
            abort(500);
        }
    }

    public function run()
    {
        if (request()->get('site-name') != 'appdev') {
            return response()->json(['data' => 'Cron jobs are in testing mode']);
        }

        $results = [];
        // Currently just set to one job but can link to the database
        $results[] = $this->testJob();
        $results[] = $this->testJobWithCSV();

        return response()->json([
            'message' => 'Cron jobs have run for ' . request()->get('site-name'),
            'jobs' => $results,
        ]);
    }

    /**
     * Send emails for allocations opening/closing
     */
    public function allocationReminders()
    {
        // TODO: Get seasons that are opening/closing

        // TODO: Get users that have allocations for those seasons

        // TODO: Send emails to users who have not completed their allocation
    }

    /**
     * Sample test cron job
     */
    public function testJob()
    {
        if (request()->get('site-name') != 'appdev') {
            return 'Cron jobs are in testing mode';
        }

        /** Create a new request with the email content */
        $request = new Request([
            'template' => 'none',
            'to' => [['email' => 'daniels@imscomply.com.au', 'name' => 'Daniel S']],
            'subject' => 'Cron job test 1',
            'body' => 'Cron job test 1',
        ]);

        /** Send using the email controller - passing in the request */
        $emailController = new EmailController();
        $emailController->sendEmail($request);

        return 'Sent test';
    }

    /**
     * Sample test cron job with a CSV
     */
    public function testJobWithCSV()
    {
        if (request()->get('site-name') != 'appdev') {
            return 'Cron jobs are in testing mode';
        }

        /** Create a temp file and push CSV rows to it */
        $tmpFile = tmpfile();
        $fileUri = stream_get_meta_data($tmpFile)['uri'];
        $csv = fopen($fileUri, 'w');
        fputcsv($csv, ['Heading 1', 'Heading 2']);
        fputcsv($csv, ['test', 'test2']);
        fputcsv($csv, ['test', 'test2']);
        fputcsv($csv, ['test', 'test2']);
        fputcsv($csv, ['test', 'test2']);
        fclose($csv);

        /** Create a new request with the email content and attach the file */
        $request = new Request([
            'template' => 'none',
            'to' => [['email' => 'daniels@imscomply.com.au', 'name' => 'Daniel S']],
            'subject' => 'Cron job test 2',
            'body' => 'Cron job test 2',
            'file' => new \Illuminate\Http\UploadedFile($fileUri, 'report.csv'),
        ]);

        /** Send using the email controller - passing in the request */
        $emailController = new EmailController();
        $emailController->sendEmail($request);

        return 'Sent test';
    }
}
