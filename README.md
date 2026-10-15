# Shiftly

Shift planning for supermarket teams. A manager builds the weekly rota, employees see their shifts, swap them with colleagues, ask for time off and say when they can work.

Built with **Laravel 13** (JSON API, Sanctum tokens) and **React 18 + TypeScript** (Vite, TanStack Query). English and Dutch, light and dark mode, works on a phone.

## Try it

On the login screen, press **Try as manager** or **Try as employee**. Every visitor gets their own sandbox team with three weeks of shifts, a few swap and time-off requests and one deliberate planning mistake. Sandbox teams are deleted after 24 hours.

Fixed accounts (password `password`):

| Role     | Email                   |
| -------- | ----------------------- |
| Manager  | `manager@shiftly.test`  |
| Employee | `employee@shiftly.test` |

## What it does

**Manager**
- Week rota grid: one row per employee, planned hours against contract hours.
- Add, edit and delete shifts. Drag a shift to another day or person to move it.
- **Copy last week** in one click. Shifts that already exist are skipped, so pressing it twice is safe.
- Rule warnings, shown while you type and again on the saved rota:
  - two shifts for one person at the same time,
  - less than 11 hours rest between shifts (the Dutch Arbeidstijdenwet minimum),
  - more hours than the contract.
  They are warnings, not blocks: sometimes a manager has no choice, but they should see it.
- Approve or decline shift swaps and time-off requests.
- Dashboard: team hours, shifts per day, who is working today, recent activity.

**Employee**
- My week, with the option to offer a shift to colleagues.
- Pick up shifts colleagues have offered. The manager approves before the shift moves.
- Request time off and withdraw it while it's still pending.
- Weekly availability. Days you can't work show as hatched cells in the manager's rota.

## How it's put together

```
app/
  Support/Rota/ShiftRules.php   planning rules, plain PHP, no database
  Services/RotaService.php      loads shifts and runs the rules on them
  Services/DemoTeamBuilder.php  builds a believable team (seeder + demo buttons)
  Http/Controllers/Api/         thin controllers, one per resource
  Http/Requests/                validation
  Policies/                     who may do what (manager vs employee, same team only)
frontend/src/
  lib/rules.ts                  the same rules in TypeScript for instant feedback
  pages/                        one file per screen
  i18n/en.ts, nl.ts             all interface text
```

Choices worth mentioning:

- **The rules live in one plain class.** `ShiftRules` takes simple value objects instead of Eloquent models, so it's unit tested without a database. The frontend has a TypeScript copy so a manager sees a warning before saving; the API stays the source of truth and returns its own warnings on every save.
- **Every query is scoped to the user's team**, and policies check the team again on every write. Feature tests cover the cross-team cases (another manager can't edit your shifts, you can't plan someone from another store).
- **Swaps go through the manager.** Picking up a shift only marks it pending. Approving moves the shift and returns rule warnings for the new owner, for example when the colleague who picked it up goes over their contract.
- **Token auth instead of cookies.** The React app and API share an origin in production, but tokens keep the API usable from anything (a phone app, curl) and make the demo flow simple.
- **No chart or icon library.** The gauge, bar chart and icons are small hand-written SVGs.

## Running locally

Requirements: PHP 8.3+, Composer, Node 20+.

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve                 # API on http://localhost:8000

cd frontend
npm install
npm run dev                       # app on http://localhost:5173, proxies /api to :8000
```

To serve the built app from Laravel instead: `cd frontend && npm run build && npm run publish:laravel`, then open http://localhost:8000.

## Tests

```bash
php artisan test          # 63 tests: rules, auth, shifts, swaps, time off, availability, demo
vendor/bin/pint --test    # code style

cd frontend
npm test                  # rules, dates, translations, components
npm run lint && npm run typecheck
```

GitHub Actions runs all of the above on every push.

## Deploying

The `Dockerfile` builds the frontend, installs PHP dependencies and serves both from one container. `render.yaml` deploys it to Render's free plan: create a new Blueprint from this repository and that's it. The database is SQLite inside the container, so the data resets to the seeded team on every deploy, which suits a demo.

## License

MIT
