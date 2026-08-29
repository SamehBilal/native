<?php

/**
 * Native UI — Theme Tokens
 *
 * Published via `php artisan vendor:publish --tag=native-ui-config`.
 * Edit to customize your app's visual identity in one place.
 *
 * For dynamic per-tenant theming, use Native\Mobile\UI\Theme::merge([...])
 * from a service provider. Runtime merges deep-merge on top of these values.
 *
 * Decision log: /docs/NATIVE-UI-REWRITE-PLAN.md (D — theme layer)
 */

return [

    /*
    |---------------------------------------------------------------------------
    | Theme
    |---------------------------------------------------------------------------
    |
    | Color tokens (open-ended map), 4 radii, 4 font sizes, font family.
    |
    | "on-X" means "color of content placed ON a surface of color X"
    |   — i.e., text/icons on that background.
    |
    | The token map is OPEN-ENDED: add any key your design needs (e.g. a
    | `warning` pair) to both blocks and `bg-theme-warning` /
    | `text-theme-on-warning` / `border-theme-warning` resolve immediately.
    | Theme classes also accept opacity modifiers — `bg-theme-primary/15`
    | is the tonal-fill idiom (the alpha applies to the dark companion
    | too). In PHP (layout chrome builders, dynamic styling) read tokens
    | with the appearance-aware `theme()` helper: `theme('primary')`.
    |
    | Color tokens accept:
    |   - CSS hex: '#B91C1C', '#F00', or with alpha '#8B5CF680' (#RRGGBBAA)
    |   - Tailwind palette names: 'red-300', 'orange-800'
    |   - Opacity modifiers on either: 'red-300/20', '#8B5CF6/50'
    |
    | Dark mode is auto-derived from `light` when `dark` is not set. To opt
    | into explicit dark tokens, fill out the `dark` block.
    |
    | The default pairs meet WCAG AA (4.5:1) — if you customize, keep each
    | `on-*` color at 4.5:1 contrast against its background token.
    |
    */

    'theme' => [

        'light' => [
            // Primary brand color — near-black, shadcn/Uber-style: filled
            // buttons and key actions read as bold black-on-white chrome
            // rather than a tinted brand color.
            'primary' => '#18181B',
            'on-primary' => '#FFFFFF',

            // Secondary / muted action color — a light neutral fill with
            // dark text, matching shadcn's "secondary" button.
            'secondary' => '#F4F4F5',
            'on-secondary' => '#18181B',

            // Surface = cards, sheets, dialogs. Background = page root.
            'surface' => '#FFFFFF',
            'on-surface' => '#18181B',
            'background' => '#FAFAFA',
            'on-background' => '#18181B',

            // Surface variant = filled text fields, muted tonal surfaces.
            // on-surface-variant = muted label/hint text on those surfaces.
            'surface-variant' => '#F4F4F5',
            'on-surface-variant' => '#71717A',

            // Outline = neutral borders (text fields, dividers, cards).
            // outline-variant = softer edges: hairline dividers, card seams.
            'outline' => '#E4E4E7',
            'outline-variant' => '#F0F0F1',

            // Destructive actions — maps to `variant="destructive"` on components.
            'destructive' => '#DC2626',
            'on-destructive' => '#FFFFFF',

            // Success / "safe to proceed" — confirmations, verified badges.
            'success' => '#16A34A',
            'on-success' => '#FFFFFF',

            // Tertiary accent — for highlights, badges, emphasis not covered by primary.
            'accent' => '#2563EB',
            'on-accent' => '#FFFFFF',
        ],

        'dark' => [
            // Leave empty or partial to auto-derive from `light` (luminance inversion).
            // Specify any token here to override the derived value.
            'primary' => '#FAFAFA',
            'on-primary' => '#18181B',

            'secondary' => '#27272A',
            'on-secondary' => '#FAFAFA',

            'surface' => '#18181B',
            'on-surface' => '#FAFAFA',
            'background' => '#09090B',
            'on-background' => '#FAFAFA',

            'surface-variant' => '#27272A',
            'on-surface-variant' => '#A1A1AA',

            'outline' => '#3F3F46',
            'outline-variant' => '#27272A',

            'destructive' => '#F87171',
            'on-destructive' => '#18181B',

            'success' => '#4ADE80',
            'on-success' => '#052E16',

            'accent' => '#60A5FA',
            'on-accent' => '#18181B',
        ],

        // Corner radii (points / dp) — crisp and modest, shadcn-style,
        // rather than the very rounded "pill" look.
        'radius-sm' => 6,
        'radius-md' => 10,
        'radius-lg' => 16,
        'radius-full' => 9999,

        // Font size scale (points / sp).
        'font-sm' => 14,
        'font-md' => 16,
        'font-lg' => 20,
        'font-xl' => 24,

    ],

    /*
    |---------------------------------------------------------------------------
    | Fonts
    |---------------------------------------------------------------------------
    |
    | Semantic names for bundled fonts (resources/fonts/ file tokens, minus
    | the extension). Use an alias anywhere a font token works — the `font`
    | attribute (`font="accent"`), chrome ->font() builders, or the layout
    | $font property. The `default` alias is the app-wide default font:
    | 'System' resolves to the platform face (San Francisco on iOS, Roboto
    | on Android); set a bundled token to apply it everywhere. Download one
    | with `php artisan native:font Inter --default`. Per-element `font`
    | attributes and font-serif / font-mono classes still win over the default.
    |
    |   'fonts' => [
    |       'default' => 'Inter-Regular',
    |       'accent'  => 'DynaPuff-Regular',
    |   ],
    |
    */

    'fonts' => [
        'default' => 'System',
    ],

];
