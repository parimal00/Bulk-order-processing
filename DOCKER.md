# Docker Runbook

## What is included

- `app`: Laravel app (`php artisan serve`) on `http://localhost:8000`
- `queue`: Queue worker (`php artisan queue:work`)
- `vite`: Vite dev server on `http://localhost:5173`
- `mysql`: MySQL 8.4 on host port `3307`

## Start everything

```bash
docker compose up --build -d
```

## Watch logs

```bash
docker compose logs -f app queue vite mysql
```

## Stop everything

```bash
docker compose down
```

## Rebuild from scratch

```bash
docker compose down -v
docker compose build --no-cache
docker compose up -d
```

## Useful commands

Run artisan command:

```bash
docker compose exec app php artisan about
```

Run migrations manually:

```bash
docker compose exec app php artisan migrate --force
```

Run npm build:

```bash
docker compose exec app npm run build
```

Run tests:

```bash
docker compose exec app php artisan test
```
