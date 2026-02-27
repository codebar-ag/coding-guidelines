# Coding Guidelines

Shared Cursor AI rules and skills for codebar-ag Laravel projects. The AI uses these to follow consistent conventions for PHP, Laravel, testing, frontend, and services.

**Rule index:** See [RULES.md](RULES.md) for the full rule structure and file reference.

---

## Prerequisites

- A Laravel project where you want to use these guidelines
- [Laravel Boost](https://github.com/laravel/boost) (for syncing rules/skills into your project)

---

## 1. Install Laravel Boost

In your Laravel project:

```bash
composer require laravel/boost --dev
php artisan boost:install
```

---

## 2. Activate the MCP server (optional)

If your editor supports MCP (Model Context Protocol), configure it to use the Boost MCP server so the AI has full context. See the [Laravel Boost documentation](https://github.com/laravel/boost) for your editor’s setup.

---

## 3. Add and sync the guidelines

Choose one of the following depending on how you work with this repo.

### When you are **not** pulling from `main`

1. Copy the **`skills`** folder from this repo into your AI config folder (e.g. `.cursor/skills` or your editor’s equivalent).
2. In your Laravel project, run:

   ```bash
   php artisan boost:update
   ```

### When you **are** pulling from the `main` branch (recommended)

In your Laravel project, add this repo as a Boost skill source and update:

```bash
php artisan boost:add-skill codebar-ag/coding-guidelines
php artisan boost:update
```

After this, the AI in your project will automatically use the rules and skills from this repository.
