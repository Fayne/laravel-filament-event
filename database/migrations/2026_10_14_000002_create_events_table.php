<?php

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Fayne\CMS\Models\Admin;
use Fayne\Event\Models\Event;
use Fayne\Event\Models\EventCategory;
use Fayne\StoreLocator\Models\Store;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $defaultLocale = (string) config('filament-cms.default_locale', 'en');

        if (! Schema::hasTable('event_categories')) {
            Schema::create('event_categories', function (Blueprint $table) use ($defaultLocale) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('locale')->default($defaultLocale);
                $table->unsignedBigInteger('fallback_id')->nullable();

                $table->string('title');
                $table->string('subtitle')->nullable();
                $table->text('description')->nullable();
                $table->unsignedSmallInteger('weight')->default(99);
                $table->datetimes();
                $table->softDeletes();

                $table->index(['fallback_id', 'locale']);
                $table->unique(['tenant_id', 'title', 'locale']);
            });
        }

        if (! Schema::hasTable('events')) {
            Schema::create('events', function (Blueprint $table) use ($defaultLocale) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->ulid('public_id');

                $table->string('locale')->default($defaultLocale);
                $table->unsignedBigInteger('fallback_id')->nullable();

                $table->foreignIdFor(EventCategory::class)
                    ->nullable()
                    ->constrained()
                    ->nullOnDelete();

                if (config('filament-event.event_store_relationship') === BelongsTo::class) {
                    $table->foreignIdFor(Store::class)
                        ->nullable()
                        ->constrained()
                        ->nullOnDelete();
                }

                $table->string('promoter_type')->nullable();
                $table->unsignedInteger('promoter_id')->nullable();
                $table->string('internal_title');
                $table->string('title');
                $table->string('slug')->nullable();
                $table->string('subtitle')->nullable();
                $table->mediumText('description')->nullable();
                $table->boolean('shown_in_homepage')->default(true);
                $table->boolean('is_bookable')->default(false);
                $table->boolean('is_charged')->default(false);
                $table->string('booking_auth_mode')->default('both');
                $table->unsignedSmallInteger('stock_type')->default(0)->comment('0:event,1:store,2:timeslot');
                $table->string('currency')->default('CNY');
                $table->decimal('price')->nullable();
                $table->boolean('status')->default(false);
                $table->dateTime('start_datetime')->nullable();
                $table->dateTime('end_datetime')->nullable();
                $table->text('redemption_code')->nullable();
                $table->boolean('need_verify')->default(false);
                $table->unsignedInteger('stock')->nullable();
                $table->unsignedSmallInteger('weight')->default(0);
                $table->datetimes();
                $table->softDeletes();

                $table->index(['locale', 'status']);
                $table->index(['fallback_id', 'locale']);
                $table->unique(['tenant_id', 'public_id', 'locale']);
                $table->unique(['tenant_id', 'internal_title', 'locale']);
            });
        }

        Schema::create('event_timeslots', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignIdFor(Event::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Store::class)
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->unsignedInteger('batch_id')->nullable();
            $table->date('date')->nullable();
            $table->string('start_time', 10)->nullable();
            $table->string('end_time', 10)->nullable();
            $table->unsignedInteger('stock')->nullable();
            $table->datetimes();
            $table->softDeletes();
        });

        Schema::create('batch_insert_event_slot_records', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignIdFor(Admin::class)->nullable()->constrained()->nullOnDelete();
            $table->json('detail')->nullable();
            $table->datetimes();
            $table->softDeletes();
        });

        Schema::create('event_orders', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignIdFor(Event::class)->nullable()->constrained()->cascadeOnDelete();
            $table->foreignIdFor(config('filament-event.models.user'))->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUlid('timeslot_id')
                ->nullable()
                ->constrained('event_timeslots')
                ->nullOnDelete();
            $table->foreignIdFor(Store::class)->nullable()->constrained()->nullOnDelete();
            $table->string('out_trade_no', 50)->unique()->comment('商户订单号');
            $table->decimal('total_amount')->default(0);
            $table->decimal('discount_amount')->default(0);
            $table->decimal('tax_amount')->default(0);
            $table->string('payment_method')->nullable();
            $table->json('metadata')->nullable();
            $table->json('notes')->nullable(); // Admin notes
            $table->json('customer_note')->nullable(); // Customer message
            $table->foreignUlid('checkout_id')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->string('status', 20)->default('reserved');
            $table->unsignedSmallInteger('quantity')->default(1)->comment('人数');
            $table->text('redemption_code')->nullable();
            $table->text('first_name')->nullable();
            $table->text('last_name')->nullable();
            $table->text('gender')->nullable();
            $table->string('area_code', 10)->nullable();
            $table->text('phone_number')->nullable();
            $table->string('check_in_code')->nullable()->unique();
            $table->dateTime('check_in_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->datetimes();
            $table->softDeletes();
        });

        Schema::create('event_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignIdFor(Event::class)->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unique(['tenant_id', 'name']);
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);
            $table->enum('condition', ['and', 'or'])->default('and');
            $table->json('conditions')->nullable();
            $table->boolean('end_other_rules')->default(true);
            $table->string('action_type')->default('registration');
            $table->unsignedSmallInteger('weight')->default(0);
            $table->datetimes();
            $table->softDeletes();
        });

        Schema::create('event_store', function (Blueprint $table) {
            $table->foreignIdFor(Event::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Store::class)->constrained()->cascadeOnDelete();
            $table->unsignedInteger('stock')->nullable();
            $table->datetimes();
            $table->primary(['event_id', 'store_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('event_rules');
        Schema::dropIfExists('event_orders');
        Schema::dropIfExists('event_timeslots');
        Schema::dropIfExists('batch_insert_event_slot_records');
        Schema::dropIfExists('event_store');
        Schema::dropIfExists('events');
        Schema::dropIfExists('event_categories');
    }
};
