# Пошаговая инструкция по исправлению ошибки деплоя

## ⚠️ Текущая проблема
SSH аутентификация не работает при деплое через GitHub Actions.

## 🔧 Решение (выберите один из вариантов)

---

## Вариант 1: Обновить SSH ключ (РЕКОМЕНДУЕТСЯ)

### Шаг 1: Подключитесь к VPS
```bash
ssh ваш-пользователь@ваш-vps-ip
```
Замените `ваш-пользователь` и `ваш-vps-ip` на ваши данные.

### Шаг 2: Создайте новый SSH ключ для деплоя
```bash
ssh-keygen -t ed25519 -C "github-deploy" -f ~/.ssh/github_deploy
```
Нажмите Enter (не вводите passphrase - это важно для автоматического деплоя!)

### Шаг 3: Добавьте публичный ключ в authorized_keys
```bash
cat ~/.ssh/github_deploy.pub >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
chmod 600 ~/.ssh/github_deploy
```

### Шаг 4: Скопируйте приватный ключ
```bash
cat ~/.ssh/github_deploy
```
Скопируйте **ВСЕ** содержимое (включая `-----BEGIN OPENSSH PRIVATE KEY-----` и `-----END OPENSSH PRIVATE KEY-----`)

### Шаг 5: Обновите GitHub Secrets

1. Откройте ваш репозиторий на GitHub
2. Перейдите: **Settings** → **Secrets and variables** → **Actions**
3. Найдите секрет `VPS_SSH_KEY` и нажмите "Update"
4. Вставьте скопированный ключ (весь, с заголовками)
5. Сохраните

### Шаг 6: Проверьте другие секреты

Убедитесь, что существуют и правильно заполнены:
- `VPS_HOST` - IP адрес вашего VPS (например: `185.104.113.132`)
- `VPS_USER` - имя пользователя (например: `deploy` или `root`)

### Шаг 7: Протестируйте деплой

1. Перейдите в **Actions** на GitHub
2. Выберите workflow **"Deploy to VPS (Alternative with rsync)"**
3. Нажмите **"Run workflow"** → **"Run workflow"**
4. Следите за логами

---

## Вариант 2: Использовать существующий ключ

Если у вас уже есть рабочий SSH ключ на компьютере:

### Шаг 1: Скопируйте ваш приватный ключ
```bash
# На вашем компьютере (не на VPS)
cat ~/.ssh/id_rsa
# ИЛИ
cat ~/.ssh/id_ed25519
```

### Шаг 2: Добавьте публичный ключ на VPS

```bash
# На вашем компьютере
ssh-copy-id ваш-пользователь@ваш-vps-ip
```

### Шаг 3: Обновите GitHub Secret
- Скопируйте содержимое приватного ключа
- Вставьте в `VPS_SSH_KEY` на GitHub

---

## Вариант 3: Проверить настройки SSH на сервере

### Проверьте права доступа
```bash
# На VPS
ls -la ~/.ssh/
```

Должно быть:
- `.ssh/` - права `700` (drwx------)
- `.ssh/authorized_keys` - права `600` (-rw-------)

### Исправить права (если нужно)
```bash
chmod 700 ~/.ssh
chmod 600 ~/.ssh/authorized_keys
```

### Проверьте конфигурацию SSH
```bash
sudo nano /etc/ssh/sshd_config
```

Должно быть:
```
PubkeyAuthentication yes
PermitRootLogin yes  # или prohibit-password
```

После изменений:
```bash
sudo systemctl restart sshd
```

---

## 🧪 Тестирование

### Тест 1: С вашего компьютера
```bash
ssh -i ~/.ssh/ваш-ключ ваш-пользователь@ваш-vps-ip
```

Если работает - проблема в GitHub Secrets.

### Тест 2: GitHub Actions

Я создал 2 workflow файла:

1. **`deploy.yml`** - обновленная версия с debug режимом
2. **`deploy-alternative.yml`** - альтернативная версия с rsync (запускается вручную)

Попробуйте запустить `deploy-alternative.yml` вручную:
1. GitHub → Actions
2. Выберите "Deploy to VPS (Alternative with rsync)"
3. Run workflow

---

## 📝 Частые ошибки

### Ошибка: "Permission denied (publickey)"
- **Причина**: Ключ не добавлен в authorized_keys
- **Решение**: Выполните Шаг 3 из Варианта 1

### Ошибка: "Too many authentication failures"
- **Причина**: SSH пробует много ключей
- **Решение**: Укажите конкретный ключ `-i ~/.ssh/github_deploy`

### Ошибка: "Host key verification failed"
- **Причина**: VPS не в known_hosts
- **Решение**: Workflow автоматически добавляет (ssh-keyscan)

---

## 🆘 Если ничего не помогло

1. Проверьте логи SSH на сервере:
```bash
sudo tail -f /var/log/auth.log
```

2. Подключитесь с verbose:
```bash
ssh -vvv -i ~/.ssh/ключ пользователь@хост
```

3. Проверьте, что пользователь из `VPS_USER` существует:
```bash
cat /etc/passwd | grep ваш-пользователь
```

4. Проверьте владельца .ssh:
```bash
ls -la ~ | grep ssh
# Должен быть владельцем ваш пользователь
```

---

## ✅ После успешного деплоя

1. Удалите debug режим из `deploy.yml` (уберите `debug: true`)
2. Переключитесь обратно на автоматический деплой при push в main
3. Удалите файл `deploy-alternative.yml` (если не нужен)

---

## 📌 Контрольный список

- [ ] SSH ключ создан на VPS
- [ ] Публичный ключ добавлен в authorized_keys
- [ ] Права на файлы правильные (700 для .ssh, 600 для ключей)
- [ ] Приватный ключ скопирован целиком (с BEGIN/END строками)
- [ ] GitHub Secret `VPS_SSH_KEY` обновлен
- [ ] GitHub Secrets `VPS_HOST` и `VPS_USER` правильные
- [ ] SSH подключение работает с локального компьютера
- [ ] Тестовый деплой через Actions успешен
