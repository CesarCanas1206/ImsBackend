<?php
namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Booking;
use App\Models\Calendar;
use App\Models\Collection;
use App\Models\CollectionField;
use App\Models\Hirer;
use App\Models\HirerUser;
use App\Models\User;
use Illuminate\Http\Request;

class BookingController extends APIController
{
    public function index()
    {
        if (request()->has('onlyDeleted')) {
            $results = Booking::onlyTrashed()
                ->leftJoin('form', 'form.id', '=', 'booking.form_id')
                ->select(['booking.*', 'form.name', 'form.reference']);
        } else {
            $results = Booking::leftJoin('form', 'form.id', '=', 'booking.form_id')
                ->select(['booking.*', 'form.name', 'form.reference']);
        }
        if (request()->has('type')) {
            $notValue = strstr(request()->type, '!') === false ? false : true;
            $value = $notValue ? str_replace('!', '', request()->type) : request()->type;
            $results = $results->where('type', ($notValue ? '!=' : '='), $value);
        }

        $results = $results->get();
        $results = $results->map([$this, 'mapFieldsToValues']);
        $results = $results->map([$this, 'mapFurther']);
        return $this->showAll($results);
    }

    public function show($id)
    {
        $result = Booking::with(['fields', 'usage'])
            ->without('hirer')
            ->withTrashed()
            ->where('id', $id)
            ->get()
            ->map([$this, 'mapFieldsToValues']);

        $result = $result->first();

        if (!empty($result->season_id)) {
            $result->season = Collection::where('id', $result->season_id)
                ->get()
                ->map([$this, 'mapFieldsToValues'])
                ->first();
        }

        $result->hirer = Hirer::with('fields')
            ->where('id', $result->hirer_id)
            ->get()
            ->map([$this, 'mapFieldsToValues'])
            ->first();
        $result->hirer_users = HirerUser::where('hirer_id', $result->hirer_id)->get()->map(function ($hu) {
            $hu = $this->mapFieldsToValues($hu);
            $hu->user = $this->mapFieldsToValues($hu->user);
            return $hu;
        });

        if (!empty($result->usage)) {
            $result->usage = $result->usage->map(function ($u) {
                $u = $this->mapFieldsToValues($u);
                if (isset($u->asset)) {
                    $u->asset = $this->mapFieldsToValues($u->asset);
                    if (isset($u->asset->parent)) {
                        $u->asset->parent = $this->mapFieldsToValues($u->asset->parent);
                    }
                }
                return $u;
            });
        }

        return $this->showOne($result);
    }

    /**
     * Function to speed up the loading of the list of bookings (non allocation)
     */
    public function bookingList()
    {
        $results = Booking::with(['fields'])
            ->without(['hirer', 'usage'])
            ->where('type', '!=', 'allocation')
            ->get()
            ->map(function ($booking) {
                return $this->mapFieldsToValues($booking, false);
            });

        $allAssets = [];
        \DB::table('asset')
            ->get()
            ->sortBy->name
        // Map assets to array with id as key
            ->each(function ($asset) use (&$allAssets) {
                $allAssets[$asset->id] = $asset;
            });

        $assetController = new AssetController();
        /** For assets the use has access to, add the label (with parent name) and get the children */
        foreach ($allAssets as $key => $asset) {
            if (!empty($asset->parent_id)) {
                $asset->name = $assetController->getParentName($asset->parent_id, $allAssets) . ' - ' . $asset->name;
            }
            $allAssets[$key] = $asset;
        }

        /** Map out the forms, hirers, users and usage for the bookings */
        $bookingIds = $results->pluck('id')->toArray();
        $userIds = $results->pluck('user_id')->toArray();
        $hirerIds = $results->pluck('hirer_id')->toArray();
        $formIds = $results->pluck('form_id')->toArray();

        $allForms = [];
        \DB::table('form')
            ->whereIn('id', $formIds)
            ->get(['id', 'name', 'reference'])
            ->each(function ($form) use (&$allForms) {
                $allForms[$form->id] = $form;
            });

        $allHirers = [];
        \DB::table('Hirer')
            ->whereIn('id', $hirerIds)
            ->get(['id', 'name'])
            ->each(function ($hirer) use (&$allHirers) {
                $allHirers[$hirer->id] = $hirer;
            });

        $allUsers = [];
        User::whereIn('id', $userIds)
            ->get(['id', 'name', 'first_name', 'last_name', 'email'])
            ->each(function ($user) use (&$allUsers) {
                $allUsers[$user->id] = $user;
            });

        $allUsage = [];
        \DB::table('usage')
            ->whereIn('parent_id', $bookingIds)
            ->get()
            ->each(function ($usage) use ($allAssets, &$allUsage) {
                if (!empty($usage->asset_id) && isset($allAssets[$usage->asset_id])) {
                    $usage->asset = $this->mapFieldsToValues($allAssets[$usage->asset_id]);
                }
                $allUsage[$usage->parent_id][] = $usage;
            });

        /** Add the values to each booking for user, hirer, form and usage */
        $results = $results->map(function ($booking) use ($allUsers, $allUsage, $allHirers, $allForms) {
            if (!empty($booking->user_id)) {
                $booking->user = $allUsers[$booking->user_id] ?? [];
            }
            if (!empty($booking->hirer_id) && isset($allHirers[$booking->hirer_id])) {
                $booking->hirer = $allHirers[$booking->hirer_id];
            }
            if (!empty($booking->form_id) && isset($allForms[$booking->form_id])) {
                $booking->name = $allForms[$booking->form_id]->name;
                $booking->reference = $allForms[$booking->form_id]->reference;
            }
            $booking->usage = $allUsage[$booking->id] ?? [];
            return $booking;
        });

        return $this->showAll($results);
    }

    public function show_old($id)
    {
        $result = Booking::withTrashed()->where('id', $id)->get()
            ->map([$this, 'mapFieldsToValues'])
            ->map([$this, 'mapFurther']);

        $result = $result->map(function ($collection) {
            if (!empty($collection->season_id)) {
                $collection->season = Collection::where('id', $collection->season_id)->get()->map([$this, 'mapFieldsToValues'])->first();
            }
            return $collection;
        });

        $result = $result->first();
        return $this->showOne($result);
    }

    public function store(Request $request)
    {
        $new = Booking::create($request->all());
        $this->addResponses($request, $new->id, Booking::class);
        $this->clearCache();
        return $this->show($new->id);
    }

    public function update(Request $request, Booking $booking)
    {
        $booking->fill($request->all())->save();

        $this->addResponses($request, $booking->id, Booking::class);

        $this->clearCache();
        return $this->show($booking->id);
    }

    public function destroy(Request $request, Booking $booking)
    {
        $booking->delete();

        CollectionField::where('collection_id', $booking->id)->delete();
        Calendar::where('parent_id', $booking->id)->delete();

        $this->clearCache();

        return response()->json(['data' => 'Deleted', $booking->id]);
    }

    public function dashboard()
    {
        $allHirers = $allAssets = [];
        // Get assets
        $getAssets = \DB::table('asset')
            ->whereNull('asset.deleted_at')
            ->get();

        // Map assets to array with id as key
        foreach ($getAssets as $asset) {
            $allAssets[$asset->id] = $asset;
        }

        $results = Calendar::where('start', '>', date('Y-m-d 00:00'))->where('start', '<', date('Y-m-d 23:59:59'))
            ->orderBy('start')->limit(30)->get();
        $results = $results->map([$this, 'mapFieldsToValues']);

        $bookingIds = $results->pluck('parent_id')->toArray();
        // Get hirers
        $getHirers = \DB::table('hirer')
            ->whereNull('hirer.deleted_at')
            ->join('booking', function ($join) use ($bookingIds) {
                $join->on('hirer.id', '=', 'booking.hirer_id')
                    ->whereIn('booking.id', $bookingIds);
            })
            ->get(['hirer.*', 'booking.id as booking_id']);

        // Map hirers to array with id as key
        foreach ($getHirers as $hirer) {
            $allHirers[$hirer->booking_id] = $hirer;
        }

        $assetController = new AssetController();
        // Venue    Hirer    Date    Time    Description
        $results = $results->map(function ($collection) use ($allHirers, $allAssets, $assetController) {
            $asset = $allAssets[$collection->asset_id];
            $assetParent = !empty($asset->parent_id)
            ? $assetController->getParentName($asset->parent_id, $allAssets) . ' - '
            : '';
            return [
                'id' => $collection->id,
                'asset' => [
                    'id' => $collection->asset_id,
                    'name' => !empty($asset) ? $assetParent . $asset->name : '',
                ],
                'hirer' => [
                    'id' => $collection->parent_id,
                    'name' => isset($allHirers[$collection->parent_id]) ? $allHirers[$collection->parent_id]->name : '',
                ],
                'date' => date('Y-m-d', strtotime($collection->start)),
                'title' => $collection->title,
                'start' => $collection->start,
                'end' => $collection->end,
            ];
        });
        return $this->showAll($results);
    }

    public function mapFurther($collection)
    {
        if (!empty($collection->asset_id)) {
            $collection->asset = Asset::where('id', $collection->asset_id)->with('fields')->get()->map([$this, 'mapFieldsToValues'])->first();
        }
        if (!empty($collection->hirer)) {
            $collection->hirer = $this->mapFieldsToValues($collection->hirer);
        } elseif (!empty($collection->parent_id)) {
            $collection->hirer = Hirer::select('hirer.*')->join('booking', 'booking.hirer_id', '=', 'hirer.id')->where('booking.id', $collection->parent_id)->get()->map([$this, 'mapFieldsToValues'])->first();
        }

        return $collection;
    }

    public function bookings()
    {
        $results = Asset::with(['assets', 'fields'])->without('parent')->where('parent_id', null)->get()
            ->map([$this, 'mapFieldsToValues'])
            ->filter(function ($item) {
                return (empty($item->visible) || $item->visible === 'Yes');
            });
        $this->formatResults($results);
        return $this->showAll($results);
    }

    public function formatResults(&$collection)
    {
        $collection->each(function (&$item) {
            if (!empty($item->assets)) {
                $children = $this->formatResults($item->assets);
                if (!empty($children->count())) {
                    $item->assets = $children;
                }
            }
            unset($item['parent']);
        });

        return $collection->map([$this, 'mapFieldsToValues']);
    }

    /**
     * Return a list of bookings related to the provided asset id
     */
    public function assetBookings($id)
    {
        $ids = \DB::table('usage')
            ->whereNull('deleted_at')
            ->where('asset_id', $id)
            ->pluck('parent_id')
            ->toArray();

        $results = Booking::leftJoin('form', 'form.id', '=', 'booking.form_id')
            ->whereIn('booking.id', $ids)
            ->select(['booking.*', 'form.name']);

        $results = $results->get();
        $results = $results->map([$this, 'mapFieldsToValues']);
        $results = $results->map([$this, 'mapFurther']);
        return $this->showAll($results);
    }

}
