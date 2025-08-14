# Рабочий процесс для предотвращения ошибок при слиянии

## Перед созданием PR:
1. **Синхронизация с основной веткой:**
   ```bash
   git checkout dev
   git pull origin dev
   git merge origin/main  # или master
   ```

2. **Локальное тестирование:**
   ```bash
   php -l services.php  # проверка синтаксиса PHP
   ```

## При конфликте в PR:
1. **НЕ редактируй вручную в GitHub!** 
2. **Локально в терминале:**
   ```bash
   git checkout your-branch
   git pull origin main
   git mergetool  # автоматически откроется VSCode
   ```

3. **В VSCode:**
   - Используй кнопки "Accept Current" / "Accept Incoming"
   - НЕ редактируй код вручную
   - Сохрани и закрой

4. **Завершение:**
   ```bash
   git add .
   git commit -m "Resolve merge conflicts"
   git push
   ```

## Проверка синтаксиса PHP:
```bash
# Проверить все PHP файлы
find . -name "*.php" -exec php -l {} \;
```

## Рекомендуемые настройки GitHub:
- Отключи "Require pull request reviews" для своих веток
- Включи "Allow auto-merge"
- Используй "Squash and merge" вместо "Create merge commit"
