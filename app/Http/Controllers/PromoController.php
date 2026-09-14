<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Model\PromoBanner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorInstance;
use Inertia\Inertia;
use Inertia\Response;

final class PromoController extends Controller
{
    /**
     * Панель управления промо-баннерами (доступ только у роли admin).
     */
    public function index(): Response
    {
        $banners = PromoBanner::query()
            ->orderByDesc('id')
            ->get()
            ->map(fn (PromoBanner $banner): array => $banner->toAdminArray())
            ->all();

        return Inertia::render('Promo', [
            'banners' => $banners,
        ]);
    }

    /**
     * Создание или редактирование промо-баннера.
     */
    public function edit(): RedirectResponse
    {
        $post = request()->post();

        $validator = Validator::make($post, [
            'banner_id'    => 'nullable|integer',
            'title'        => 'nullable|string|max:255',
            'message'      => 'required|string|max:1000',
            'button_text'  => 'nullable|string|max:100',
            'button_url'   => 'nullable|string|max:255',
            'starts_at'    => 'nullable|date',
            'ends_at'      => 'nullable|date',
            'dismiss_days' => 'required|integer|min:1|max:365',
            'is_active'    => 'boolean',
        ]);

        $validator->after(function (ValidatorInstance $validator) use ($post): void {
            $startsAt = $post['starts_at'] ?? null;
            $endsAt = $post['ends_at'] ?? null;

            if ($startsAt && $endsAt && $endsAt < $startsAt) {
                $validator->errors()->add('ends_at', lng('error.promo_period'));
            }
        });

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $bannerId = !empty($post['banner_id']) ? (int) $post['banner_id'] : null;
        $banner = $bannerId !== null ? PromoBanner::find($bannerId) : new PromoBanner();

        if (!$banner) {
            return back()->with('error', lng('error.edit_promo'));
        }

        $banner->title = !empty($post['title']) ? $post['title'] : null;
        $banner->message = trim((string) $post['message']);
        $banner->button_text = !empty($post['button_text']) ? $post['button_text'] : null;
        $banner->button_url = !empty($post['button_url']) ? $post['button_url'] : null;
        $banner->starts_at = !empty($post['starts_at']) ? $post['starts_at'] : null;
        $banner->ends_at = !empty($post['ends_at']) ? $post['ends_at'] : null;
        $banner->dismiss_days = (int) $post['dismiss_days'];
        $banner->is_active = !empty($post['is_active']);

        if (empty($banner->save())) {
            return back()->with('error', lng('error.' . ($bannerId !== null ? 'edit_promo' : 'add_promo')));
        }

        return back()->with('success', lng('success.' . ($bannerId !== null ? 'edit_promo' : 'add_promo')));
    }

    /**
     * Удаление промо-баннера.
     */
    public function delete(): RedirectResponse
    {
        $post = request()->post();

        $validator = Validator::make($post, [
            'banner_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $banner = PromoBanner::find((int) $post['banner_id']);

        if (!$banner || !$banner->delete()) {
            return back()->with('error', lng('error.delete_promo'));
        }

        return back()->with('success', lng('success.delete_promo'));
    }

    /**
     * Быстрое включение/выключение промо-баннера.
     */
    public function toggle(): RedirectResponse
    {
        $post = request()->post();

        $validator = Validator::make($post, [
            'banner_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $banner = PromoBanner::find((int) $post['banner_id']);

        if (!$banner) {
            return back()->with('error', lng('error.toggle_promo'));
        }

        $banner->is_active = !$banner->is_active;

        if (!$banner->save()) {
            return back()->with('error', lng('error.toggle_promo'));
        }

        return back()->with('success', lng('success.toggle_promo'));
    }
}
