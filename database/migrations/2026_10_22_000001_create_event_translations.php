<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['event' => 'event_id', 'event_category' => 'event_category_id'] as $owner => $foreign) {
            $model = config("filament-event.models.$owner");
            $name = config("filament-event.tables.{$owner}_translations");
            if (Schema::hasTable($name)) {
                continue;
            }
            Schema::create($name, function (Blueprint $table) use ($model, $foreign, $owner) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->foreignId($foreign)->constrained((new $model)->getTable())->cascadeOnDelete();
                $table->string('locale');
                $table->string('title');
                if ($owner === 'event') {
                    $table->string('slug')->nullable();
                }
                $table->string('subtitle')->nullable();
                $table->mediumText('description')->nullable();
                $table->boolean('is_active')->default(false);
                $table->timestamps();
                $table->unique([$foreign, 'locale']);
            });
        }
    }
    public function down(): void
    {
        Schema::dropIfExists(config('filament-event.tables.event_translations'));
        Schema::dropIfExists(config('filament-event.tables.event_category_translations'));
    }
};
