<?php

namespace Modules\Inventory\Traits;

use Modules\Inventory\Models\{
    ItemWarehouse,
    Warehouse,
    InventoryConfiguration,
    InventoryTransaction,
    Inventory
};
use App\Models\Tenant\{
    Configuration,
    Establishment,
    SaleNoteItem,
    Item
};
Use Throwable;
use Modules\Item\Models\ItemLotsGroup;
use Modules\Item\Models\ItemLot;

trait InventoryTrait
{
    public function optionsEstablishment() {
        $records = Establishment::all();

        return collect($records)->transform(function($row) {
            return  [
                'id' => $row->id,
                'description' => $row->description
            ];
        });
    }

    public function optionsItem() {
        $records = Item::where([['item_type_id', '01'], ['unit_type_id', '!=','ZZ']])
        ->whereIsActive()
        ->whereNotIsSet()
        ->get();

        return collect($records)->transform(function($row) {
            return  [
                'id' => $row->id,
                'description' => $row->description
            ];
        });
    }

    public function optionsItemWareHouse() {
        $establishment_id = auth()->user()->establishment_id;
        $current_warehouse = Warehouse::where('establishment_id', $establishment_id)->first();

        $records = Item::whereWarehouse()->where([['item_type_id', '01'], ['unit_type_id', '!=','ZZ']])->whereNotIsSet()->get();

        return collect($records)->transform(function($row) use ($current_warehouse) {
            return  [
                'id' => $row->id,
                'description' => $row->description,
                'lots_enabled' => (bool)$row->lots_enabled,
                'lots' => $row->item_lots->where('has_sale', false)->where('warehouse_id', $current_warehouse->id)->transform(function($row) {
                    return [
                        'id' => $row->id,
                        'series' => $row->series,
                        'date' => $row->date,
                        'item_id' => $row->item_id,
                        'warehouse_id' => $row->warehouse_id,
                        'has_sale' => (bool)$row->has_sale,
                        'lot_code' => ($row->item_loteable_type) ? (isset($row->item_loteable->lot_code) ? $row->item_loteable->lot_code:null):null
                    ];
                }),
            ];
        });
    }

    public function optionsItemWareHousexId($warehouse_id) {

        $records = Item::whereHas('warehouses', function($query) use ($warehouse_id){
            $query->where('warehouse_id', $warehouse_id);
        })
        ->where([['item_type_id', '01'], ['unit_type_id', '!=','ZZ']])->whereNotIsSet()->get();

        return collect($records)->transform(function($row) use ($warehouse_id) {
            return  [
                'id' => $row->id,
                'description' => $row->description,
                'lots_enabled' => (bool)$row->lots_enabled,
                'series_enabled' => (bool)$row->series_enabled,
                'lots' => $row->item_lots->where('has_sale', false)->where('warehouse_id', $warehouse_id)->transform(function($row) {
                    return [
                        'id' => $row->id,
                        'series' => $row->series,
                        'date' => $row->date,
                        'item_id' => $row->item_id,
                        'warehouse_id' => $row->warehouse_id,
                        'has_sale' => (bool)$row->has_sale,
                        'lot_code' => ($row->item_loteable_type) ? (isset($row->item_loteable->lot_code) ? $row->item_loteable->lot_code:null):null
                    ];
                })->values(),
            ];
        });
    }

    public function optionsItemFull() {
        $records = Item::where([['item_type_id', '01'], ['unit_type_id', '!=','ZZ']])
        ->whereNotIsSet()
        ->whereIsActive()
        ->get();

        return collect($records)->transform(function($row) {
            return  [
                'id' => $row->id,
                'description' => ($row->internal_id) ? "{$row->internal_id} - {$row->description}" :$row->description,
                'lots_enabled' => (bool) $row->lots_enabled,
                'series_enabled' => (bool) $row->series_enabled,
                'lots' => $row->item_lots->where('has_sale', false)->transform(function($row) {
                    return [
                        'id' => $row->id,
                        'series' => $row->series,
                        'date' => $row->date,
                        'item_id' => $row->item_id,
                        'warehouse_id' => $row->warehouse_id,
                        'has_sale' => (bool)$row->has_sale,
                        'lot_code' => ($row->item_loteable_type) ? (isset($row->item_loteable->lot_code) ? $row->item_loteable->lot_code:null):null
                    ];
                }),
                'lots_group' => collect($row->lots_group)->transform(function($row){
                    return [
                        'id'  => $row->id,
                        'code' => $row->code,
                        'quantity' => $row->quantity,
                        'date_of_due' => $row->date_of_due,
                        'checked'  => false
                    ];
                })
            ];
        });
    }

    public function findInventoryTransaction($id) {

        return InventoryTransaction::findOrFail($id);

    }

    public function optionsInventoryTransaction($type) {

        $records = InventoryTransaction::where('type', $type)->get();

        return $records;
    }

    public function optionsWarehouse() {
        $records = Warehouse::all();

        return collect($records)->transform(function($row) {
            return  [
                'id' => $row->id,
                'description' => $row->description
            ];
        });
    }

    public function findItem($item_id) {
        return Item::find($item_id);
    }

    public function findWarehouse($establishment_id = null) {
        if ($establishment_id) {
            $establishment = Establishment::find($establishment_id);
        }
        else {
            $establishment = auth()->user()->establishment;
        }

        return Warehouse::firstOrCreate([
            'establishment_id' => $establishment->id
        ], [
            'description' => 'Almacén '.$establishment->description
        ]);
    }

    private function createInitialInventory($item_id, $quantity, $warehouse_id) {
        return Inventory::create([
            'type' => 1,
            'description' => 'Stock inicial',
            'item_id' => $item_id,
            'warehouse_id' => $warehouse_id,
            'quantity' => $quantity
        ]);
    }

    private function createInventoryKardex($model, $item_id, $quantity, $warehouse_id) {
        $model->inventory_kardex()->create([
            'date_of_issue' => date('Y-m-d'),
            'item_id' => $item_id,
            'warehouse_id' => $warehouse_id,
            'quantity' => $quantity,
        ]);
    }


    private function updateStock($item_id, $quantity, $warehouse_id) {

        $inventory_configuration = InventoryConfiguration::firstOrFail();

        $item_warehouse = ItemWarehouse::firstOrNew(['item_id' => $item_id, 'warehouse_id' => $warehouse_id]);
        $item_warehouse->stock = $item_warehouse->stock + $quantity;

        if($quantity < 0 && $item_warehouse->item->unit_type_id !== 'ZZ'){
            if (($inventory_configuration->stock_control) && ($item_warehouse->stock < 0)){
                throw new \RuntimeException("El producto {$item_warehouse->item->description} no tiene suficiente stock!");
            }
        }
        $item_warehouse->save();
    }

    public function checkInventory($item_id, $warehouse_id) {
        $inventory = Inventory::where('item_id', $item_id)
            ->where('warehouse_id', $warehouse_id)
            ->first();

        return ($inventory)?true:false;
    }

    public function initializeInventory() {
        $warehouse = $this->findWarehouse();
        $items = Item::all();

        foreach ($items as $item) {
            if (!$this->checkInventory($item->id, $warehouse->id)) {
                $inventory = $this->createInitialInventory($item->id, $item->stock, $warehouse->id);
            }
        }
    }

    public function findWarehouseById($warehouse_id) {
        return Warehouse::findOrFail($warehouse_id);
    }

    public function findSaleNoteItem($sale_note_item_id) {
        return SaleNoteItem::find($sale_note_item_id);
    }

    private function createInventoryKardexSaleNote($model, $item_id, $quantity, $warehouse_id, $sale_note_item_id) {

        $sale_note_kardex = $model->inventory_kardex()->create([
            'date_of_issue' => date('Y-m-d'),
            'item_id' => $item_id,
            'warehouse_id' => $warehouse_id,
            'quantity' => $quantity,
        ]);

        $sale_note_item = $this->findSaleNoteItem($sale_note_item_id);
        $sale_note_item->inventory_kardex_id = $sale_note_kardex->id;
        $sale_note_item->update();
    }

    private function deleteInventoryKardex($model, $inventory_kardex_id) {
        $model->inventory_kardex()->where('id',$inventory_kardex_id)->delete();
    }

    private function deleteAllInventoryKardexByModel($model) {
        $model->inventory_kardex()->delete();
    }

    private function updateDataLots($document_item){

        if(isset($document_item->item->IdLoteSelected) )
        {
            if($document_item->item->IdLoteSelected != null)
            {
                $lot = ItemLotsGroup::find($document_item->item->IdLoteSelected);
                $lot->quantity =  $lot->quantity + $document_item->quantity;
                $lot->save();
            }
        }

        if(isset($document_item->item->lots) )
        {
            foreach ($document_item->item->lots as $it) {

                if($it->has_sale == true)
                {
                    $r = ItemLot::find($it->id);
                    $r->has_sale = false;
                    $r->save();
                }

            }
        }

    }


    private function deleteItemLots($item){

        $i_lots_group = isset($item->item->lots_group) ? $item->item->lots_group:[];

        $lot_group_selected = collect($i_lots_group)->first(function($row){
            return $row->checked;
        });

        if($lot_group_selected){

            $lot = ItemLotsGroup::find($lot_group_selected->id);
            $lot->quantity =  $lot->quantity + $item->quantity;
            $lot->save();

        }

        if(isset($item->item->lots)){

            foreach ($item->item->lots as $it) {

                if($it->has_sale == true){

                    $ilt = ItemLot::find($it->id);
                    $ilt->has_sale = false;
                    $ilt->save();

                }

            }
        }

    }

    public function voidedDocumentItemSet($detail)
    {

        $document_item = $detail;

        $item = Item::findOrFail($document_item->item_id);

        foreach ($item->sets as $it) {

            $ind_item  = $it->individual_item;
            $item_set_quantity  = ($it->quantity) ? $it->quantity : 1;
            $presentationQuantity = 1;
            $document = $document_item->document;
            $factor = 1;
            $warehouse = $this->findWarehouse();
            $this->createInventoryKardex($document_item->document, $ind_item->id, ($factor * ($document_item->quantity * $presentationQuantity * $item_set_quantity)), $warehouse->id);
            if(!$document_item->document->sale_note_id && !$document_item->document->order_note_id) $this->updateStock($ind_item->id, ($factor * ($document_item->quantity * $presentationQuantity * $item_set_quantity)), $warehouse->id);

        }
    }

    public function verifyHasSaleLots($purchase_item)
    {
        $validated = true;

        $items = $purchase_item->lots;

        foreach ($items as $element) {

            if($element->has_sale == 1)
            {
                $validated = false;
                break;
            }
        }

        if($validated == false)
        {
            throw new \RuntimeException("El producto {$purchase_item->item->description} contiene series vendidas!");
        }
    }

    public function verifyHasSaleLotsGroup($purchase_item)
    {
        if($purchase_item->item->lots_enabled && $purchase_item->lot_code )
        {
            $lot_group = ItemLotsGroup::where('code', $purchase_item->lot_code)->first();

            if(!$lot_group)
            {
                throw new \RuntimeException("El lote {$purchase_item->lot_code} no existe!");
            }

            if( (int)$lot_group->quantity != (int)$purchase_item->quantity)
            {
                throw new \RuntimeException("Los productos del lote {$purchase_item->lot_code} han sido vendidos!");
            }

        }

    }

    public static function deleteItemSeriesAndGroup($purchase_item)
    {
        $series = $purchase_item->lots;
        foreach ($series as $row) {
            $it = ItemLot::findOrFail($row->id);
            $it->delete();
        }
        if($purchase_item->item->lots_enabled && $purchase_item->lot_code )
        {
            $lot_group = ItemLotsGroup::where('code', $purchase_item->lot_code)->firstOrFail();
            if(!$lot_group)
            {
                throw new \RuntimeException("El lote {$purchase_item->lot_code} no existe!");
            }
            $lot_group->delete();
        }
    }

    private function updateStockPurchase($item_id, $quantity, $warehouse_id) {

        $inventory_configuration = InventoryConfiguration::firstOrFail();

        $item_warehouse = ItemWarehouse::firstOrNew(['item_id' => $item_id, 'warehouse_id' => $warehouse_id]);
        $item_warehouse->stock = $item_warehouse->stock + $quantity;

        $item_warehouse->save();
    }

}
