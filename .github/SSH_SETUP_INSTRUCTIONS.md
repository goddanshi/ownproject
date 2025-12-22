# Инструкция по настройке SSH для деплоя

## Проблема
```
ssh: handshake failed: ssh: unable to authenticate, attempted methods [none publickey], no supported methods remain
```

Это означает, что SSH-ключ в GitHub Secrets не соответствует ключу на сервере.

## Быстрая диагностика

Попробуйте подключиться к VPS с вашего компьютера:
```bash
ssh -v your-user@your-vps-ip
```

Если подключается - значит проблема в GitHub Secrets. Если нет - проблема в настройках SSH на сервере.

## Решение

### Шаг 1: На VPS сервере (под пользователем для деплоя)

```bash
# Войдите на сервер под нужным пользователем
ssh your-deploy-user@your-vps-ip

# Создайте SSH ключ (если его нет)
ssh-keygen -t ed25519 -C "github-actions-deploy"
# Нажмите Enter несколько раз (не используйте passphrase для автоматического деплоя)

# Добавьте публичный ключ в authorized_keys
cat ~/.ssh/id_ed25519.pub >> ~/.ssh/authorized_keys

# Установите правильные права
chmod 700 ~/.ssh
chmod 600 ~/.ssh/authorized_keys
chmod 600 ~/.ssh/id_ed25519

# Скопируйте ПРИВАТНЫЙ ключ для GitHub
cat ~/.ssh/id_ed25519
```

### Шаг 2: В GitHub

1. Перейдите в ваш репозиторий на GitHub
2. Settings → Secrets and variables → Actions
3. Обновите или создайте секрет `VPS_SSH_KEY`:
   - Скопируйте весь вывод команды `cat ~/.ssh/id_ed25519`
   - Вставьте в значение секрета (включая строки `-----BEGIN OPENSSH PRIVATE KEY-----` и `-----END OPENSSH PRIVATE KEY-----`)

4. Убедитесь, что также настроены:
   - `VPS_HOST` - IP адрес или домен вашего VPS
   - `VPS_USER` - имя пользователя для деплоя (например: deploy, www-data, или root)

### Шаг 3: Проверка

Проверьте, что SSH ключ работает локально:

```bash
# На вашем компьютере (замените на ваши данные)
ssh -i ~/.ssh/id_ed25519 your-user@your-vps-ip

# Если подключается без пароля - ключ работает правильно
```

### Альтернативный метод (если используете существующий ключ)

Если у вас уже есть SSH ключ для доступа к серверу:

```bash
# На вашем компьютере
cat ~/.ssh/id_rsa
# ИЛИ
cat ~/.ssh/id_ed25519

# Скопируйте содержимое и добавьте в GitHub Secret VPS_SSH_KEY
```

### Важные замечания

1. **Формат ключа**: Убедитесь, что копируете весь ключ целиком, включая заголовки
2. **Права доступа**: На сервере должны быть правильные права (700 для .ssh, 600 для файлов)
3. **Пользователь**: Пользователь в `VPS_USER` должен совпадать с владельцем ключа
4. **Тип ключа**: Рекомендуется использовать ed25519, но также работают RSA ключи

### Проверка текущих настроек

Проверьте какой пользователь используется в GitHub Secrets:
- Откройте Settings → Secrets and variables → Actions
- Проверьте значение `VPS_USER`
- Убедитесь, что этот пользователь существует на VPS
- Убедитесь, что у этого пользователя есть доступ к `/var/www`

### Если ошибка продолжается

1. Проверьте логи SSH на сервере:
```bash
sudo tail -f /var/log/auth.log
```

2. Попробуйте подключиться с verbose режимом:
```bash
ssh -vvv -i /path/to/key user@host
```

3. Убедитесь, что на сервере разрешена аутентификация по ключу:
```bash
sudo nano /etc/ssh/sshd_config

# Должно быть:
PubkeyAuthentication yes
PasswordAuthentication no  # Опционально для безопасности

# Перезапустите SSH:
sudo systemctl restart sshd
```
