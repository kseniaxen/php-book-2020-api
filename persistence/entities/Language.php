<?php
// Сущность "Язык"
class Language {
  // уникальный id - будет генерироваться БД при вставке строки
  protected $id;
  // название языка
  protected $name;
  // приоритет
  protected $priority;
  // Конструктор
  function __construct(
    $name
    , $priority
    , $id = 0
    ) {
    $this->id = $id;
    $this->name = $name;
    $this->priority = $priority;
  }
  // вставка строки о языке в БД
  function create () {
    try {
      // Получаем контекст для работы с БД
      $pdo = getDbContext();
      // Превращаем объект в массив
      $ar = get_object_vars($this);
      // Удаляем из него первый элемент - id потому что его создаст СУБД
      array_shift($ar);
      // Если в БД еще нет языков с таким названием -
      // сначала добавляем ее, иначе - сразу возвращаем данные о ней
      $ps = $pdo->prepare("SELECT * FROM `Language` WHERE `name` = :name");
      //Пытаемся выполнить запрос на получение данных
      $resultCode = $ps->execute($ar);
      if ($resultCode && ($row = $ps->fetch())) {
        $this->id = $row['id'];
      } else {
        // Готовим sql-запрос добавления строки в таблицу "Языки"
        $ps = $pdo->prepare("INSERT INTO `Language` (`name`, `priority`) VALUES (:name, :priority)");
        // Выполняем запрос к БД для добавления записи
        $ps->execute($ar);
        $this->id = $pdo->lastInsertId();
      }
      return get_object_vars($this);
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
  // Редактирование строки о языке по ее идентификатору
  function edit() {
    try {
      // Удаляем старую версию строки из БД
      Language::delete($this->id);
      // Вставляем новую версию строки в БД
      $this->create();
    } catch (PDOException $e) {
      $err = $e->getMessage();
      if (substr($err, 0, strrpos($err, ":")) == 'SQLSTATE[23000]:Integrity constraint violation') {
        return 1062;
      } else {
        return $e->getMessage();
      }
    }
  }
  // Удаление строки из БД по идентификатору
  function delete ($id) {
    try {
      // Получаем контекст для работы с БД
      $pdo = getDbContext();
      // Готовим sql-запрос удаления строки из таблицы  "Язык"
      $pdo->exec("DELETE FROM `Language` WHERE `id` = $id");
    } catch (PDOException $e) {
      $err = $e->getMessage();
      if (substr($err, 0, strrpos($err, ":")) == 'SQLSTATE[23000]:Integrity constraint violation') {
        return 1062;
      } else {
        return $e->getMessage();
      }
    }
  }
  // Получение списка всех языков из БД
  static function getAll () {
    // Переменная для подготовленного запроса
    $ps = null;
    // Переменная для результата запроса
    $languages = null;
    try {
        // Получаем контекст для работы с БД
        $pdo = getDbContext();
        // пытаемся получить все записи и языков
        $ps = $pdo->prepare("SELECT * FROM `Language`");
        // Выполняем
        $ps->execute();
        //Сохраняем полученные данные в ассоциативный массив
        $languages = $ps->fetchAll();
        return $languages;
    } catch (PDOException $e) {
        echo $e->getMessage();
        return false;
    }
  }
  // Получение списка языков из БД
  static function filter($args) {
    // Переменная для подготовленного запроса
    $ps = null;
    // Переменная для результата запроса
    $languages = null;
    try {
        // Получаем контекст для работы с БД
        $pdo = getDbContext();
        // пытаемся получить все записи и языках
        $ps = $pdo->prepare("SELECT * FROM `Languages` WHERE `name` LIKE '{$args['startsWith']}%'");
        // Выполняем
        $ps->execute();
        //Сохраняем полученные данные в ассоциативный массив
        $countries = $ps->fetchAll();
        return $countries;
    } catch (PDOException $e) {
        echo $e->getMessage();
        return false;
    }
  }
}