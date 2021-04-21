# Wordquant 0.0.9

Результат выполнения тестового задания для компании "Матрикс". Стек: PHP 7.4, Laravel 8, MySQL (MariaDB 10.5).

## Установка

1. Склонируйте репозиторий (`git clone`) и перейдите в появившуюся директорию.

2. Установите с помощью Composer необходимые зависимости (`php composer.phar install`).

3. Создайте базу данных.

4. Скопируйте/переименуйте файл **.env.example** в **.env**.

5. Откройте **.env**. Укажите в поле *APP_URL* адрес, по которому планируете обращаться в приложении, а также укажите параметры подключения к новой базе данных в секции *DB_*.

6. Выполните миграцию базы данных (`php artisan migrate`).

7. Сгенерируйте ключ шифрования (`php artisan key:generate`).
