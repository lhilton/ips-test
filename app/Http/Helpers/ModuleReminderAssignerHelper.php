<?php

namespace App\Http\Helpers;

use App\Tag;
use App\User;
use App\Module;
use App\Http\Helpers\InfusionsoftHelper;

class ModuleReminderAssignerHelper
{
    protected $iHelper;

    public function __construct()
    {
        $this->iHelper = app()->make(InfusionsoftHelper::class);
    }

    /**
     * Loads a user from the database, queries Infusionsoft for its user data
     * and appends the infusionsoft id purchased products and completed modules
     * to the user object.
     *
     * @param  string $email
     * @return Illuminate\Http\Response;
     */
    public function sendModuleReminderForUser(string $email)
    {
        $user = $this->getUserFromEmail($email);

        if(! $user->infusionsoft_id)
        {
            return response()->json([
                'success' => false,
                'message' => 'User has no infusionsoft account'
            ], 422);
        }

        if(count($user->products) === 0)
        {
            return response()->json([
                'success' => false,
                'message' => 'User has no purchased products'
            ], 422);
        }

        $tag = $this->getTagForUser($user);

        $result = $this->iHelper
                    ->addTag($user->infusionsoft_id, $tag->infusionsoft_id);

        return response()->json([
            'success' => $result,
            'message' => $result
                            ? 'Tag submitted successfully: ' . $tag->name
                            : 'There was an error adding the tag to Infusionsoft'
        ], $result ? 200 : 422);
    }

    /**
     * Loads a user from the database, queries Infusionsoft for its user data
     * and appends the infusionsoft id purchased products and completed modules
     * to the user object.
     *
     * @param  string $email
     * @return \App\User $user
     */
    public function getUserFromEmail(string $email)
    {
        $user = User::where('email', $email)->firstOrFail();

        $iUser = $this->iHelper->getContact($user->email);

        $user->infusionsoft_id = isset($iUser['Id']) ? $iUser['Id'] : null;
        $user->products = isset($iUser['_Products']) ? $iUser['_Products'] : '';
        $user->products = $user->products
                            ? $this->convertPurchaseToArray($user->products)
                            : [];
        $user->completed = $this->getCompletedModules($user, $user->products);
        $user->makeHidden('completed_modules');
        return $user;
    }

    /**
     * Converts the purchased products string out of Infusionsoft to an array
     *
     * @param  string $purchased
     * @return Array
     */
    public function convertPurchaseToArray(string $purchased)
    {
        return explode(',', $purchased);
    }

    /**
     * Builds a collection of completed modules from a user based on the desired
     * notification logic of purchase-order, module completion order (desc).
     *
     * @param  \App\User $user
     * @param  array $purchased
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCompletedModules(User $user, array $purchased)
    {
        return $user
            ->completed_modules
            ->whereIn('course_key', $purchased)
            ->groupBy('course_key')
            ->map(function($module) {
                return $module->sortBy('name');
            })
            ->sortBy(function($elem, $idx) use ($purchased) {
                return array_search($idx, $purchased);
            })
            ->flatten();
    }

    /**
     * Determine the Tag needed to dispatch notifications.
     *
     * @param  \App\User $user
     * @return \App\Tag
     */
    public function getTagForUser(User $user)
    {
        $tag = $this->getTagForUserWithNoCompletions($user);
        if(! $tag) $tag = $this->getNextTagForUser($user);
        return $tag;
    }

    /**
     * Determines if the user has not yet completed any modules, if not it will
     * select the correct module based on users purchases.
     *
     * @param  \App\User $user
     * @return \App\Tag|null will return a null when user has completions.
     */
    public function getTagForUserWithNoCompletions(User $user)
    {
        if($user->completed->count() === 0)
        {
            $ck = $user->products[0];
            $module = Module::where('course_key', $ck)
                        ->orderBy('name')
                        ->first();

            return $this->getTagForModule($module);
        }

        return null;
    }

    /**
     * Determines the next appropriate module to send notification for, or if
     * the user has completed all (or last) module in all purchases.
     *
     * @param  \App\User $user
     * @return \App\Tag
     */
    public function getNextTagForUser(User $user)
    {
        $modules = Module::whereIn('course_key', $user->products)
                    ->get()
                    ->sortBy('name')
                    ->groupBy('course_key');

        foreach($user->products as $product)
        {
            $last = $user
                        ->completed
                        ->where('course_key', $product)
                        ->sortBy('name')
                        ->last();

            $midx = $modules[$product]->search(function($m) use ($last) {
                return $m->id === $last->id;
            });

            if(isset($modules[$product][$midx + 1]))
                return $this->getTagForModule($modules[$product][$midx + 1]);
        }

        // By the time we reach this line, we have found that the user did have
        // ongoing work, however there are no "next" modules in their respective
        // courses. The cause of this is that they finished all max-level
        // modules in all courses.
        return Tag::where('name', 'Module reminders completed')->first();
    }

    public function getTagForModule(Module $module)
    {
        return Tag::where('name', 'Start ' . $module->name . ' Reminders')
                ->orderBy('name')
                ->first();
    }
}
