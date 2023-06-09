<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('customer');
            $table->uuid('reference')->unique();
            $table->json('billets');
            $table->decimal('amount', 10, 2);
            $table->integer('installment')->default(1);
            $table->string('token')->nullable();
            $table->string('transaction')->nullable();
            $table->enum('method', ['tef', 'ecommerce', 'picpay']);
            $table->enum('payment_type', ['credit', 'debit', 'pix'])->nullable();
            $table->enum('status', [
                'created',
                'approved',
                'canceled',
                'refused',
                'expired',
                'chargeback',
            ])->default('created');

            $table->text('receipt')->nullable();
            $table->json('customer_origin')->nullable();
            $table->unsignedBigInteger('terminal_id')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payments');
    }
}
