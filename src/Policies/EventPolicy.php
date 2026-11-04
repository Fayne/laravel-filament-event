<?php

namespace Fayne\Event\Policies;

use Fayne\CMS\Models\Admin;

class EventPolicy
{
    public function before($admin, string $ability, $record = null): ?bool
    {
        if ($record instanceof \Illuminate\Database\Eloquent\Model
            && (app(\Fayne\CMS\Support\TenantContext::class)->id() === null
                || (string) $record->getAttribute('tenant_id') !== (string) app(\Fayne\CMS\Support\TenantContext::class)->id())) {
            return false;
        }

        return null;
    }

    public function viewAny(Admin $admin): bool
    {
        return $this->allows($admin, 'tenant.viewAnyEvent');
    }
    public function view(Admin $admin): bool
    {
        return $this->allows($admin, 'tenant.viewEvent');
    }
    public function create(Admin $admin): bool
    {
        return $this->allows($admin, 'tenant.createEvent');
    }
    public function update(Admin $admin): bool
    {
        return $this->allows($admin, 'tenant.updateEvent');
    }
    public function delete(Admin $admin): bool
    {
        return $this->allows($admin, 'tenant.deleteEvent');
    }

    private function allows(Admin $admin, string $permission): bool
    {
        return $admin->is_active && \Fayne\CMS\Support\Authorization\Authorization::allows($admin, $permission);
    }
}
