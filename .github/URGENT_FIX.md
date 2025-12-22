# 🚨 СРОЧНОЕ ИСПРАВЛЕНИЕ: SSH не работает

## Проблема
```
ssh: handshake failed: ssh: unable to authenticate, attempted methods [none publickey], no supported methods remain
```

## ✅ РЕШЕНИЕ (выполните ШАГ ЗА ШАГОМ)

### Шаг 1: Проверьте текущие GitHub Secrets

1. Откройте: https://github.com/ваш-username/ownproject/settings/secrets/actions
2. Проверьте наличие:
   - `VPS_HOST`
   - `VPS_USER`
   - `VPS_SSH_KEY`

### Шаг 2: Подключитесь к VPS и создайте новый ключ

```bash
# 1. Подключитесь к VPS (замените на ваши данные)
ssh root@ваш-vps-ip

# 2. Переключитесь на пользователя деплоя (если не root)
# su - deploy
# ИЛИ если пользователь другой:
# su - ваш-пользователь

# 3. Проверьте текущую директорию
pwd
whoami

# 4. Создайте новый SSH ключ БЕЗ ПАРОЛЯ (это важно!)
ssh-keygen -t rsa -b 4096 -C "github-actions" -f ~/.ssh/github_deploy -N ""

# 5. Добавьте публичный ключ в authorized_keys
cat ~/.ssh/github_deploy.pub >> ~/.ssh/authorized_keys

# 6. Установите правильные права
chmod 700 ~/.ssh
chmod 600 ~/.ssh/authorized_keys
chmod 600 ~/.ssh/github_deploy
chmod 644 ~/.ssh/github_deploy.pub

# 7. Скопируйте ПРИВАТНЫЙ ключ
cat ~/.ssh/github_deploy
```

**ВАЖНО:** Скопируйте **ВСЕ** содержимое, включая:
```
-----BEGIN OPENSSH PRIVATE KEY-----
...весь ключ...
-----END OPENSSH PRIVATE KEY-----
```

### Шаг 3: Обновите GitHub Secrets

1. Перейдите: Settings → Secrets and variables → Actions
2. Найдите `VPS_SSH_KEY`
3. Нажмите "Update"
4. Вставьте **весь** скопированный ключ
5. Нажмите "Update secret"

### Шаг 4: Проверьте VPS_USER

**КРИТИЧЕСКИ ВАЖНО:** Убедитесь, что `VPS_USER` в GitHub Secrets совпадает с пользователем, под которым вы создали ключ!

Если вы выполняли команды под пользователем `root`, то `VPS_USER` должен быть `root`.
Если под `deploy`, то `VPS_USER` должен быть `deploy`.

Проверьте на VPS:
```bash
whoami
# Результат этой команды = значение VPS_USER
```

### Шаг 5: Запустите тестовый workflow

1. Перейдите на GitHub: Actions → "Test SSH Connection"
2. Нажмите "Run workflow" → "Run workflow"
3. Дождитесь выполнения
4. Проверьте логи

**Если тест прошел успешно:**
- Запустите основной деплой (коммит в main или вручную через Actions)

**Если тест НЕ прошел:**
- Проверьте логи (они покажут первую строку ключа)
- Убедитесь, что ключ начинается с `-----BEGIN OPENSSH PRIVATE KEY-----`
- Убедитесь, что скопировали ключ полностью

---

## 🔍 Частые ошибки

### Ошибка 1: Неправильный формат ключа
**Симптомы:** Ключ не начинается с `-----BEGIN`

**Решение:**
```bash
# На VPS выполните снова:
cat ~/.ssh/github_deploy

# Убедитесь, что копируете ВСЁ, включая BEGIN и END строки
```

### Ошибка 2: Несоответствие пользователей
**Симптомы:** Подключается, но "Permission denied"

**Решение:**
```bash
# На VPS проверьте:
ls -la ~/.ssh/

# Владельцем всех файлов должен быть текущий пользователь
# Если нет:
sudo chown -R $(whoami):$(whoami) ~/.ssh/
```

### Ошибка 3: Неправильные права доступа
**Симптомы:** "Bad permissions"

**Решение:**
```bash
chmod 700 ~/.ssh
chmod 600 ~/.ssh/authorized_keys
chmod 600 ~/.ssh/github_deploy
```

### Ошибка 4: Ключ с паролем
**Симптомы:** Запрашивает passphrase

**Решение:**
Создайте ключ ЗАНОВО с флагом `-N ""` (без пароля):
```bash
ssh-keygen -t rsa -b 4096 -C "github-actions" -f ~/.ssh/github_deploy -N ""
```

---

## 📋 Контрольный список

Пройдитесь по этому списку:

- [ ] Подключился к VPS по SSH с локального компьютера
- [ ] Определил пользователя для деплоя (root, deploy, www-data, другой)
- [ ] Создал новый SSH ключ БЕЗ пароля
- [ ] Добавил публичный ключ в authorized_keys
- [ ] Установил правильные права (700 для .ssh, 600 для файлов)
- [ ] Скопировал ВЕСЬ приватный ключ (включая BEGIN/END)
- [ ] Обновил GitHub Secret `VPS_SSH_KEY`
- [ ] Проверил, что `VPS_USER` совпадает с пользователем ключа
- [ ] Проверил, что `VPS_HOST` правильный (IP или домен)
- [ ] Запустил тестовый workflow "Test SSH Connection"
- [ ] Тест прошел успешно

---

## 🆘 Если ничего не помогает

### Вариант А: Используйте пароль (временно, для диагностики)

1. На VPS разрешите парольную аутентификацию:
```bash
sudo nano /etc/ssh/sshd_config

# Измените на:
PasswordAuthentication yes

# Сохраните и перезапустите:
sudo systemctl restart sshd
```

2. В GitHub добавьте secret `VPS_PASSWORD` с паролем пользователя

3. Обновите workflow - используйте `password` вместо `key`

### Вариант Б: Проверьте логи на VPS

```bash
# В реальном времени смотрите попытки подключения:
sudo tail -f /var/log/auth.log

# Запустите деплой и смотрите что происходит
```

### Вариант В: Используйте другого пользователя

Попробуйте создать нового пользователя специально для деплоя:

```bash
# На VPS (под root):
sudo useradd -m -s /bin/bash github-deploy
sudo usermod -aG sudo github-deploy
sudo mkdir -p /home/github-deploy/.ssh
sudo chown -R github-deploy:github-deploy /home/github-deploy/.ssh

# Переключитесь на нового пользователя:
sudo su - github-deploy

# Создайте ключ:
ssh-keygen -t rsa -b 4096 -C "github" -f ~/.ssh/github_deploy -N ""
cat ~/.ssh/github_deploy.pub >> ~/.ssh/authorized_keys
chmod 700 ~/.ssh
chmod 600 ~/.ssh/authorized_keys ~/.ssh/github_deploy

# Скопируйте ключ:
cat ~/.ssh/github_deploy
```

Затем в GitHub Secrets:
- `VPS_USER` = `github-deploy`
- `VPS_SSH_KEY` = скопированный ключ

---

## ✅ После успешного подключения

1. Запустите основной деплой
2. Если все работает, удалите debug режим из deploy.yml
3. Удалите тестовый workflow test-ssh.yml

---

## 📞 Нужна помощь?

Если ничего не помогает, отправьте мне:
1. Результат: `whoami` на VPS
2. Результат: `ls -la ~/.ssh/` на VPS
3. Первую строку приватного ключа: `head -n 1 ~/.ssh/github_deploy`
4. Скриншот GitHub Secrets (закройте значения)
5. Логи из тестового workflow
