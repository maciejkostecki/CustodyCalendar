<?php

namespace App\Services;

class ParentResolver
{
    /**
     * Resolve a logged-in email to its custody role, or null if not a parent.
     */
    public function roleForEmail(string $email): ?string
    {
        foreach (config('custody.parents') as $role => $parent) {
            if (isset($parent['email']) && strcasecmp($parent['email'], $email) === 0) {
                return $role;
            }
        }

        return null;
    }

    /**
     * The opposite custody role.
     */
    public function otherRole(string $role): string
    {
        return $role === 'father' ? 'mother' : 'father';
    }

    /**
     * All configured roles keyed by role name.
     *
     * @return array<string, array{label:string, color:string, email:string}>
     */
    public function all(): array
    {
        return config('custody.parents');
    }
}
