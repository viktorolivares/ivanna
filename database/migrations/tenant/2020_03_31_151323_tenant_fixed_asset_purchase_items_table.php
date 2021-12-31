<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TenantFixedAssetPurchaseItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        Schema::create('fixed_asset_purchase_items', function (Blueprint $table) {

            $table->increments('id');
            $table->unsignedInteger('fixed_asset_purchase_id');
            $table->unsignedInteger('fixed_asset_item_id');
            $table->text('item');
            $table->decimal('quantity',12,4);
            $table->decimal('unit_value', 16, 6);

            $table->string('affectation_igv_type_id');
            $table->decimal('total_base_igv', 12, 2);
            $table->decimal('percentage_igv', 12, 2);
            $table->decimal('total_igv', 12, 2);

            $table->string('system_isc_type_id')->nullable();
            $table->decimal('total_base_isc', 12, 2)->default(0);
            $table->decimal('percentage_isc', 12, 2)->default(0);
            $table->decimal('total_isc', 12, 2)->default(0);

            $table->decimal('total_base_other_taxes', 12, 2)->default(0);
            $table->decimal('percentage_other_taxes', 12, 2)->default(0);
            $table->decimal('total_other_taxes', 12, 2)->default(0);
            $table->decimal('total_taxes', 12, 2);

            $table->string('price_type_id');
            $table->decimal('unit_price', 16, 6);

            $table->decimal('total_value', 12, 2);
            $table->decimal('total_charge', 12, 2)->default(0);
            $table->decimal('total_discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);

            $table->text('attributes')->nullable();
            $table->text('discounts')->nullable();
            $table->text('charges')->nullable();

            $table->foreign('fixed_asset_purchase_id')->references('id')->on('fixed_asset_purchases')->onDelete('cascade');
            $table->foreign('fixed_asset_item_id')->references('id')->on('fixed_asset_items');
            $table->foreign('affectation_igv_type_id')->references('id')->on('cat_affectation_igv_types');
            $table->foreign('system_isc_type_id')->references('id')->on('cat_system_isc_types');
            $table->foreign('price_type_id')->references('id')->on('cat_price_types');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fixed_asset_purchase_items');
    }
}
