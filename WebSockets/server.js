const express = require('express');
const app = express();
const rateLimit = require('express-rate-limit');
const cors = require('cors');
const axios = require('axios');


// Middleware
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

app.use(cors({
  origin: '*',
  methods: ['GET', 'POST', 'OPTIONS'],
  allowedHeaders: ['Content-Type', 'Authorization']
}));

// 🔒 Middleware ограничения запросов
const rateLimitMiddleware = rateLimit({
  windowMs: 60 * 1000, // 1 минута
  max: 10, // максимум 10 запросов в минуту
  message: { result: 'Слишком много запросов, попробуйте позже.' }
});

app.get('/', async (req, res) =>{
    res.json({
      message: 'Hello',
    });
})
// 🧠 Функция генерации ответа от ИИ
async function generateResponse(prompt) {
  try {
    // Здесь может быть любой AI backend — пример для OpenAI совместимого API
    const response = await axios.post('https://openrouter.ai/api/v1/chat/completions', {
      model: 'meta-llama/llama-4-maverick:free',
      messages: [
        { 
        role: 'system', 
        content: `Ты — опытный и профессиональный SEO-специалист, копирайтер и редактор.
        Твоя задача — помогать пользователю создавать тексты и заголовки на русском языке, полностью соответствующие стандартам SEO, орфографии, пунктуации и стиля.

        Требования:
        1. Пиши грамотно, без ошибок, в соответствии с нормами русского языка.
        2. Используй естественные формулировки, избегай «воды» и переоптимизации.
        3. При создании заголовков учитывай:
        - длину до 60 символов (для Title);
        - наличие ключевого слова в начале;
        - привлекательность и кликабельность (CTR).
        4. Для статей:
        - структура: введение, основная часть, заключение;
        - наличие подзаголовков H2–H3 с ключами;
        - LSEO-оптимизация без потери читабельности;
        - соблюдение логики и плавных переходов.
        5. Отвечай внятно и структурировано, без воды и дублирования.
        6. При необходимости можешь предложить список ключевых слов, мета-описание, заголовок H1 и теги.

        Главная цель — помочь пользователю создать качественный SEO-текст, который будет понятен людям и эффективен для поисковых систем.`
        },
        { 
        role: 'user', 
        content: prompt 
        }
      ]
    }, {
      headers: {
        'Authorization': `Bearer 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`,
        'Content-Type': 'application/json'
      }
    });
    console.log(response.data.choices?.[0]?.message?.content?.trim());
    // Извлекаем ответ модели
    return response.data.choices?.[0]?.message?.content?.trim() || 'Пустой ответ';
    
  } catch (error) {
    console.error('Ошибка при обращении к ИИ:', error.response?.data || error.message);
    throw new Error('Ошибка при получении ответа от модели');
  }
}

// 📩 Основной эндпоинт
app.post('/api/generate', rateLimitMiddleware, async (req, res) => {
  const { prompt } = req.body;

  // Валидация
  if (!prompt) {
    return res.status(400).json({
      result: 'Параметр prompt обязателен'
    });
  }

  try {
    console.log(`📝 /api/generate - Входящий prompt: "${prompt}"`);

    const responseText = await generateResponse(prompt);

    res.json({
      result: responseText
    });
  } catch (error) {
    console.error('Ошибка при генерации ответа:', error.message);
    res.status(500).json({
      result: 'Ошибка при генерации ответа: ' + error.message
    });
  }
});

// Инициализация сервера
async function startServer() {
  try {
    // Запуск сервера
    app.listen(3000, () => {
      console.log(`Сервер запущен на порту 3000`);
      console.log(`http://localhost:3000`);
    });
  } catch (error) {
    console.error('Ошибка при запуске сервера:', error);
    process.exit(1);
  }
}

startServer();
