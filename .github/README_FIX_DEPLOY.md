# 🔧 Как исправить ошибку деплоя

## Быстрый старт (5 минут)

### 1️⃣ На VPS выполните:
```bash
ssh-keygen -t rsa -b 4096 -f ~/.ssh/github_deploy -N ""
cat ~/.ssh/github_deploy.pub >> ~/.ssh/authorized_keys
chmod 700 ~/.ssh && chmod 600 ~/.ssh/authorized_keys ~/.ssh/github_deploy
cat ~/.ssh/github_deploy
```

### 2️⃣ На GitHub:
1. Settings → Secrets → Actions
2. Обновите `VPS_SSH_KEY` → вставьте **весь** вывод команды выше
3. Убедитесь что `VPS_USER` = результат команды `whoami` на VPS

### 3️⃣ Тест:
1. Actions → "Test SSH Connection" → Run workflow
2. Если ОК → деплой заработает автоматически

---

## 📚 Подробные инструкции

- [URGENT_FIX.md](URGENT_FIX.md) - Пошаговая инструкция с пояснениями
- [DEPLOY_FIX_STEPS.md](DEPLOY_FIX_STEPS.md) - Полное руководство с вариантами
- [SSH_SETUP_INSTRUCTIONS.md](SSH_SETUP_INSTRUCTIONS.md) - Техническая документация

---

## 🎯 Workflows

1. **test-ssh.yml** - Тестирование SSH подключения (запуск вручную)
2. **deploy-alternative.yml** - Альтернативный деплой через rsync (запуск вручную)
3. **deploy.yml** - Основной деплой (автоматически при push в main)

---

## ❓ Частые вопросы

**В: Какой пользователь должен быть в VPS_USER?**
О: Тот, под которым вы создавали SSH ключ. Проверьте: `whoami`

**В: Нужен ли пароль для SSH ключа?**
О: НЕТ! Ключ должен быть без пароля (флаг `-N ""`)

**В: Где смотреть ошибки?**
О: GitHub → Actions → выберите failed workflow → смотрите логи

**В: Как проверить что ключ правильный?**
О: Он должен начинаться с `-----BEGIN OPENSSH PRIVATE KEY-----`

---

## 🚨 Если не работает

1. Прочитайте [URGENT_FIX.md](URGENT_FIX.md)
2. Запустите тестовый workflow "Test SSH Connection"
3. Проверьте логи - они покажут где проблема
4. Убедитесь что `VPS_USER` совпадает с пользователем на VPS

---

## ✅ Проверка работоспособности

После исправления:
1. Коммит в main → деплой должен пройти автоматически
2. Удалите debug режим из deploy.yml (уберите `debug: true`)
3. Удалите ненужные тестовые файлы
