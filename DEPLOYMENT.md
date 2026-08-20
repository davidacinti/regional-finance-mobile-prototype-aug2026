# Public Prototype Deployment

This Laravel prototype should be hosted on a server, not GitHub Pages.

## Recommended path

1. Push this repository to GitHub under `davidacinti`.
2. Create a new Render Web Service from the GitHub repository.
3. Render will detect `render.yaml` and build the included Dockerfile.
4. In Render, set:
   - `APP_KEY` to a generated Laravel key.
   - `APP_URL` to the Render public URL after the first deploy is created.

## Generate an APP_KEY locally

Run:

```bash
php artisan key:generate --show
```

Copy the full `base64:...` value into Render as `APP_KEY`.

## Notes

- `.env` is intentionally not committed.
- GitHub Pages cannot run this app because it is Laravel/PHP.
- Render free services may sleep after inactivity, so the first load after a pause can be slower.
