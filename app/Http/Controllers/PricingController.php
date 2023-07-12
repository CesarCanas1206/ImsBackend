<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\CollectionField;
use Illuminate\Http\Request;

class PricingController extends APIController
{
    public function getPricing(Request $request)
    {
        /** Ids of assets to get the pricing of */
        $ids = $request->ids ?? [];
        $pricingData = [];
        foreach ($ids as $assetId) {
            /** Get pricing from asset */
            $check = CollectionField::where('collection_id', $assetId)
                ->where('reference', 'pricing_id')
                ->get(['collection_id', 'value'])
                ->first();

            if (!empty($check)) {
                $pricingData[$assetId] = $check;
                continue;
            }

            /** If no pricing on asset, get from the parent */
            $parentId = Asset::where('id', $assetId)->pluck('parent_id')->first();
            if (empty($parentId)) {
                continue;
            }
            $check = CollectionField::where('collection_id', $parentId)
                ->where('reference', 'pricing_id')
                ->get(['collection_id', 'value'])
                ->first();

            if (!empty($check)) {
                $pricingData[$assetId] = $check;
            }

            /** If no pricing on parent, get from the parent's parent */
            $parentId2 = Asset::where('id', $parentId)->pluck('parent_id')->first();
            if (empty($parentId2)) {
                continue;
            }
            $check = CollectionField::where('collection_id', $parentId2)
                ->where('reference', 'pricing_id')
                ->get(['collection_id', 'value'])
                ->first();

            if (!empty($check)) {
                $pricingData[$assetId] = $check;
                continue;
            }
            /** If no pricing on parent's parent, get from the asset's asset-type */
            $assetType = CollectionField::where('collection_id', $assetId)
                ->where('reference', 'asset-type-id')
                ->pluck('value')
                ->first();

            $check = CollectionField::where('collection_id', $assetType)
                ->where('reference', 'pricing_id')
                ->get(['collection_id', 'value'])
                ->first();

            if (!empty($check)) {
                $pricingData[$assetId] = $check;
            }
        }

        /** Get the rates for each pricing item */
        $getRates = CollectionField::whereIn('collection_id', array_column($pricingData, 'value'))
            ->where('reference', 'rates')
            ->get(['collection_id', 'value'])
            ->toArray();

        $rates = [];
        foreach ($getRates as $rate) {
            $rates[$rate['collection_id']] = $rate['value'];
        }

        $results = [];
        /** Format the pricing results for each of the requested ids */
        foreach ($pricingData as $assetId => $pricingId) {
            if (!isset($rates[$pricingId['value']])) {
                continue;
            }
            $results[$assetId] = json_decode($rates[$pricingId['value']], true);
        }

        return $this->successResponse(['data' => $results]);
    }
}
