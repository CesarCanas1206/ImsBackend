<?php
namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\CollectionField;
use App\Models\Hirer;
use Illuminate\Http\Request;

class HirerController extends APIController
{
    public function index()
    {
        $results = Hirer::with('fields');

        if (request()->has('with')) {
            $results = $results->with(explode(',', request()->get('with')));
        }

        if (request()->has('user_id')) {
            $value = request()->user_id === 'me' ? request()->user()->id : request()->user_id;

            $results = $results->join('hirer_user', 'hirer_user.hirer_id', '=', 'hirer.id')
                ->where('hirer_user.user_id', $value)->select(['hirer.*', 'hirer_user.user_id']);
            request()->query->remove('user_id');
        }

        $hirerTypes = [];
        Collection::where('slug', 'hirer-type')
            ->get()
            ->map([$this, 'mapFieldsToValues'])
            ->each(function ($item) use (&$hirerTypes) {
                $hirerTypes[$item->id] = $item->name;
            });

        $sports = [];
        Collection::where('slug', 'sport')
            ->get()
            ->map([$this, 'mapFieldsToValues'])
            ->each(function ($item) use (&$sports) {
                $sports[$item->id] = [
                    'id' => $item->id,
                    'name' => $item->name];
            });

        // return $this->successResponse(['data' => $sports], 200);
        $results = $results->get()
            ->map([$this, 'mapFieldsToValues'])
            ->map(function ($item) use ($hirerTypes, $sports) {
                $hirerTypeId = substr($item->hirer_type_id, 0, 2) == '["'
                ? json_decode(stripslashes($item->hirer_type_id), true) : $item->hirer_type_id;
                $item->hirer_type = [];
                if (!empty($hirerTypeId)) {
                    if (is_array($hirerTypeId)) {
                        $hirer_type = [];
                        foreach ($hirerTypeId as $id) {
                            if (isset($hirerTypes[$id])) {
                                $hirer_type[] = $hirerTypes[$id];
                            }
                        }
                        $item->hirer_type = implode(', ', $hirer_type);
                    } else if (isset($hirerTypes[$hirerTypeId])) {
                        $item->hirer_type = $hirerTypes[$hirerTypeId];
                    }
                }
                $hirer_sport = explode(',', $item->sport_id);
                if (!empty($hirer_sport)) {
                    $hirer_sports = [];
                    foreach ($hirer_sport as $id) {
                        if (isset($sports[$id])) {
                            $hirer_sports[] = $sports[$id];
                        }
                    };
                    $item->sport = $hirer_sports;
                }
                return $item;
            });

        return $this->showAll($results);
    }

    public function show($id)
    {
        $results = Hirer::where('id', $id);
        if (request()->has('with')) {
            $results = $results->with(explode(',', request()->get('with')));
        }
        $results = $results->get();
        $results = $results->map([$this, 'mapFieldsToValues']);
        return $this->showOne($results->first());
    }

    public function store(Request $request)
    {
        $new = Hirer::create($request->all());
        $this->addResponses($request, $new->id, Hirer::class);
        return $this->show($new->id);
    }

    public function update(Request $request, Hirer $hirer)
    {
        $hirer->fill($request->all())->save();

        $this->addResponses($request, $hirer->id, Hirer::class);

        $this->clearCache();
        return $this->show($hirer->id);
    }

    public function destroy(Request $request, Hirer $hirer)
    {
        $hirer->delete();

        CollectionField::where('collection_id', $hirer->id)->delete();

        $this->clearCache();

        return response()->json(['data' => 'Deleted', $hirer->id]);
    }

    /** Get a simple list of the hirers with just id, name */
    public function simple()
    {
        $results = \DB::table('hirer')
            ->whereNull('deleted_at')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
        return $this->showAll($results);
    }

    /**
     * hirerType function
     * Return the hirer types
     * @return void
     */
    public function hirerType()
    {
        $results = Collection::where('slug', 'hirer-type')
            ->get()
            ->map([$this, 'mapFieldsToValues']);

        return $this->showAll($results);
    }

    public function hirerMemberDetails()
    {
        $results = Hirer::with('fields');

        $hirerTypes = [];
        Collection::where('slug', 'hirer-type')
            ->get()
            ->map([$this, 'mapFieldsToValues'])
            ->each(function ($item) use (&$hirerTypes) {
                $hirerTypes[$item->id] = $item->name;
            });

        $sports = [];
        Collection::where('slug', 'sport')
            ->get()
            ->map([$this, 'mapFieldsToValues'])
            ->each(function ($item) use (&$sports) {
                $sports[$item->id] = [
                    'id' => $item->id,
                    'name' => $item->name,
                    'ages' => json_decode($item->ages) ?? [],
                    'association' => json_decode($item->association) ?? [],
                    'participation' => json_decode($item->participation) ?? []];
            });

        // return $this->successResponse(['data' => $sports], 200);
        $results = $results->get()
            ->map([$this, 'mapFieldsToValues'])
            ->map(function ($item) use ($hirerTypes, $sports) {
                $hirerTypeId = substr($item->hirer_type_id, 0, 2) == '["'
                ? json_decode(stripslashes($item->hirer_type_id), true) : $item->hirer_type_id;
                $item->hirer_type = [];
                if (!empty($hirerTypeId)) {
                    if (is_array($hirerTypeId)) {
                        $hirer_type = [];
                        foreach ($hirerTypeId as $id) {
                            if (isset($hirerTypes[$id])) {
                                $hirer_type[] = $hirerTypes[$id];
                            }
                        }
                        $item->hirer_type = implode(', ', $hirer_type);
                    } else if (isset($hirerTypes[$hirerTypeId])) {
                        $item->hirer_type = $hirerTypes[$hirerTypeId];
                    }
                }
                $hirer_sport = explode(',', $item->sport_id);
                if (!empty($hirer_sport)) {
                    $hirer_sports = [];
                    foreach ($hirer_sport as $id) {
                        if (isset($sports[$id])) {
                            $hirer_sports[] = $sports[$id];
                        }
                    };
                    $item->sport = $hirer_sports;
                }
                return $item;
            });

        return $this->showAll($results);
    }

}
