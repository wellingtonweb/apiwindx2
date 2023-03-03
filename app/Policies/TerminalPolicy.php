<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Terminal;
use Illuminate\Auth\Access\HandlesAuthorization;

class TerminalPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the terminal can view any models.
     *
     * @param  App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the terminal can view the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\Terminal  $model
     * @return mixed
     */
    public function view(User $user, Terminal $model)
    {
        return true;
    }

    /**
     * Determine whether the terminal can create models.
     *
     * @param  App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the terminal can update the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\Terminal  $model
     * @return mixed
     */
    public function update(User $user, Terminal $model)
    {
        return true;
    }

    /**
     * Determine whether the terminal can delete the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\Terminal  $model
     * @return mixed
     */
    public function delete(User $user, Terminal $model)
    {
        return true;
    }

    /**
     * Determine whether the user can delete multiple instances of the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\Terminal  $model
     * @return mixed
     */
    public function deleteAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the terminal can restore the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\Terminal  $model
     * @return mixed
     */
    public function restore(User $user, Terminal $model)
    {
        return false;
    }

    /**
     * Determine whether the terminal can permanently delete the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\Terminal  $model
     * @return mixed
     */
    public function forceDelete(User $user, Terminal $model)
    {
        return false;
    }
}
