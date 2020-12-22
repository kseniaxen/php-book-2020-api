<?php
// Сущность "Книга"
class Book {
  // уникальный id - будет генерироваться БД при вставке строки
  protected $id;
  // временная метка последнего обновления
  protected $updatedAt;
  // индексированный идентификатор пользователя GoogleFireBase UserId
  protected $userId;
  // E-mail пользователя Google
  protected $userEmail;
  // заголовок
  protected $title;
  // автор
  protected $author;
  // жанр (опционально)
  protected $genre;
  // описание
  protected $description;
  // идентификатор страны предложения
  protected $countryId;
  // идентификатор города предложения
  protected $cityId;
  // идентификатор типа предложения
  protected $typeId;
  // изображение Base64 (опционально)
  protected $image;
  // активность (предложение доступно для запроса или по какой-либо причине скрыто от поиска)
  protected $active;
  // индентификатор языка
  protected $languageId;
  // дата публикации книги
  protected $publication_date;
  // Конструктор
  function __construct(
    $title
    , $author
    , $genre
    , $description
    , $countryId
    , $cityId
    , $typeId
    , $image
    , $active
    , $userId
    , $userEmail
    , $id = 0
    , $updatedAt = ''
    , $languageId
    , $publication_date
    ) {
    $this->id = $id;
    $this->updatedAt = $updatedAt;
    $this->userId = $userId;
    $this->userEmail = $userEmail;
    $this->title = $title;
    $this->author = $author;
    $this->genre = $genre;
    $this->description = $description;
    $this->countryId = $countryId;
    $this->cityId = $cityId;
    $this->typeId = $typeId;
    $this->image = $image;
    $this->active = $active;
    $this->languageId = $languageId;
    $this->publication_date = $publication_date;
  }
  // вставка строки о книге в БД
  function create () {
    try {
      // Получаем контекст для работы с БД
      $pdo = getDbContext();
      // echo '$pdo' . $pdo;
      // Готовим sql-запрос добавления строки в таблицу "Книги"
      $ps = $pdo->prepare("INSERT INTO `Books` (`userId`, `userEmail`, `title`, `author`, `genre`, `description`, `countryId`, `cityId`, `typeId`, `image`, `active`, `languageId`, `publication_date`) VALUES (:userId, :userEmail, :title, :author, :genre, :description, :countryId, :cityId, :typeId, :image, :active, :languageId, :publication_date)");
      // Превращаем объект в массив
      $ar = get_object_vars($this);
      // Удаляем из него первые два элемента - id и created_at, потому что их создаст СУБД
      array_shift($ar);
      array_shift($ar);
      // echo '$ar' . $ar;
      // Выполняем запрос к БД для добавления записи
      $ps->execute($ar);
      //
      $this->id = $pdo->lastInsertId();
      $ps = $pdo->prepare("SELECT `b`.`id`, `b`.`updatedAt`, `b`.`userId`, `b`.`userEmail`, `b`.`title`, `b`.`author`, `b`.`genre`, `b`.`description`, `b`.`publication_date`, `co`.`name` AS 'country', `ci`.`name` AS 'city', `ty`.`id` AS 'type', `b`.`image`, `b`.`active`, `la`.`id` AS 'language' FROM `Books` AS `b` INNER JOIN `Country` AS `co` ON (`b`.`countryId` = `co`.`id`) INNER JOIN `City` AS `ci` ON (`b`.`cityId` = `ci`.`id`) INNER JOIN `Type` AS `ty` ON (`b`.`typeId` = `ty`.`id`) INNER JOIN `Language` AS `la` ON (`b`.`languageId` = `la`.`id`) WHERE `b`.`id` = {$this->id}");
      return ($ps->execute() && ($row = $ps->fetch())) ? $row : null;
    } catch (PDOException $e) {
      // Если произошла ошибка - возвращаем ее текст
      $err = $e->getMessage();
      if (substr($err, 0, strrpos($err, ":")) == 'SQLSTATE[23000]:Integrity constraint violation') {
        return 1062;
      } else {
        return $e->getMessage();
      }
    }
  }
  // Редактирование строки о книге по ее идентификатору
  function edit() {
    try {
      // Удаляем старую версию строки из БД
      Book::delete($this->id);
      // Вставляем новую версию строки в БД
      return $this->create();
    } catch (PDOException $e) {
      $err = $e->getMessage();
      if (substr($err, 0, strrpos($err, ":")) == 'SQLSTATE[23000]:Integrity constraint violation') {
        return 1062;
      } else {
        return $e->getMessage();
      }
    }
  }
  // Удаление строки книге из БД по идентификатору
  function delete ($id) {
    try {
      // Получаем контекст для работы с БД
      $pdo = getDbContext();
      // Готовим sql-запрос удаления строки из таблицы "Книги"
      $pdo->exec("DELETE FROM `Books` WHERE `id` = $id");
    } catch (PDOException $e) {
      $err = $e->getMessage();
      if (substr($err, 0, strrpos($err, ":")) == 'SQLSTATE[23000]:Integrity constraint violation') {
        return 1062;
      } else {
        return $e->getMessage();
      }
    }
  }
  // Получение списка всех книг из БД
  static function filter($args) {
    // Переменная для подготовленного запроса
    $ps = null;
    // Переменная для результата запроса
    $books = null;
    try {
        // Получаем контекст для работы с БД
        $pdo = getDbContext();
        // Массив для условий запроса
        $whereClouse = [];
        // Сбор условий запроса в массив
        if (isset($args['lastId'])) {
          $whereClouse[] = "`b`.`id` < '{$args['lastId']}'";
        }
        if (isset($args['userId'])) {
          $whereClouse[] = "`b`.`userId` = '{$args['userId']}'";
        }
        if (isset($args['active'])) {
          $whereClouse[] = "`b`.`active` = '{$args['active']}'";
        }
        /* if (isset($args['title'])) {
          $whereClouse[] = "locate('{$args['title']}', `b`.`title`) > 0";
        }
        if (isset($args['author'])) {
          $whereClouse[] = "locate('{$args['author']}', `b`.`author`) > 0";
        } */
        if (isset($args['search']) && $args['search']) {
          $whereClouse[] = "((locate('{$args['search']}', `b`.`title`) > 0) OR (locate('{$args['search']}', `b`.`author`) > 0))";
        }
        if (isset($args['country']) && $args['country']->id) {
          $whereClouse[] = "`b`.`countryId` = '{$args['country']->id}'";
        }
        if (isset($args['city']) && $args['city']->id) {
          $whereClouse[] = "`b`.`cityId` = '{$args['city']->id}'";
        }
        if (isset($args['typeId'])) {
          $whereClouse[] = "`b`.`typeId` = '{$args['typeId']}'";
        }
        $whereClouseString = 'WHERE ';
        $expressionCount = 0;
        foreach ($whereClouse as $expression) {
          if ($expressionCount++ == 0) {
            $whereClouseString .=  $expression;
          } else {
            $whereClouseString .= ' AND ' . $expression;
          }
        }
        // Готовим sql-запрос чтения всех строк данных из таблицы "Книги"
        // с подключением связанных таблиц "Страна", "Город", "Тип", "Язык"
        // сортируем по идентификаторам,
        // пытаемся получить только три значения из строк, идентификаторы которых меньше заданного
        $ps = $pdo->prepare("SELECT `b`.`id`, `b`.`updatedAt`, `b`.`userId`, `b`.`userEmail`, `b`.`title`, `b`.`author`, `b`.`genre`, `b`.`description`, `b`.`publication_date`, `co`.`name` AS 'country', `ci`.`name` AS 'city', `ty`.`id` AS 'type', `b`.`image`, `b`.`active`, `la`.`id` AS 'language' FROM `Books` AS `b` INNER JOIN `Country` AS `co` ON (`b`.`countryId` = `co`.`id`) INNER JOIN `City` AS `ci` ON (`b`.`cityId` = `ci`.`id`) INNER JOIN `Type` AS `ty` ON (`b`.`typeId` = `ty`.`id`) INNER JOIN `Language` AS `la` ON (`b`.`languageId` = `la`.`id`) {$whereClouseString} ORDER BY `b`.`id` DESC LIMIT 2");
        // echo "SELECT `b`.`id`, `b`.`updatedAt`, `b`.`userId`, `b`.`title`, `b`.`author`, `b`.`genre`, `b`.`description`, `co`.`name` AS 'country', `ci`.`name` AS 'city', `ty`.`name` AS 'type', `b`.`image`, `b`.`active` FROM `Books` AS `b` INNER JOIN `Country` AS `co` ON (`b`.`countryId` = `co`.`id`) INNER JOIN `City` AS `ci` ON (`b`.`cityId` = `ci`.`id`) INNER JOIN `Type` AS `ty` ON (`b`.`typeId` = `ty`.`id`) {$whereClouseString} ORDER BY `b`.`id` DESC LIMIT 3";
        // Выполняем
        $ps->execute();
        //Сохраняем полученные данные в ассоциативный массив
        $books = $ps->fetchAll();
        return $books;
    } catch (PDOException $e) {
        echo $e->getMessage();
        return false;
    }
  }
}