# froxlor UI

The UI package provides the shared Blade components, Livewire views, layouts,
navigation collectors and browser assets used by the framework and optional
feature packages.

## Development assets

Run from `framework/`:

```bash
composer run dev:assets
```

This creates `froxlor/public/vendor/froxlor/ui` as a symlink to the package's
`dist` directory. Build or watch the package from `framework/packages/ui` with
`npm run build` or `npm run dev`.

## Published assets

For a deployable application, publish the built package assets from `framework/`:

```bash
composer run assets:publish
```

The application also publishes the assets during Composer install/update. UI
providers register their bundle through `UI::assetsDirective(...)`; all
registered bundles are rendered by `@froxlorHead`, and missing files fail
explicitly instead of silently producing an incomplete page.
