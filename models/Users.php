<?php

namespace App;

class Users extends MainController
{
    use FileController;

    public function getUserInfo()
    {
        $usersView = self::getPD()->prepare('SELECT name, photo FROM users WHERE login=:login');
        $usersView->execute(['login' => $_SESSION['login']]);
        $data = $usersView->fetch(\PDO::FETCH_ASSOC);
        return $data;
    }

    private function getUserAvatar($id)
    {
        $usersView = self::getPD()->prepare('SELECT photo FROM users WHERE id=:id');
        $usersView->execute(['id' => $id]);
        $data = $usersView->fetch(\PDO::FETCH_ASSOC);
        return $data;
    }

    public function GetUser()
    {
        $routes = explode('/', $_SERVER['REQUEST_URI']);
        $id = $routes[3];
        $usersView = self::getPD()->prepare('SELECT * FROM users WHERE id=:id');
        $usersView->execute(['id' => $id]);
        $data = $usersView->fetch(\PDO::FETCH_ASSOC);
        return $data;
    }

    private function CheckLogin($login)
    {
        $usersView = self::getPD()->prepare('SELECT login FROM users WHERE login=:login');
        $usersView->execute(['login' => $login]);
        $data = $usersView->fetch(\PDO::FETCH_ASSOC);
        return $data;
    }

    public function LoginUser()
    {
        $userModel = new Users();
        $login = $_POST['login'];
        $paswword = $_POST['password'];
        $logged = '';
        if (!empty($login) && !empty($paswword)) {
            $paswword_ver = $userModel->CryptPasswd($paswword);
            $datas = $this->AutentificationUser($paswword_ver);
            if (!empty($datas)) {
                if ($datas['login'] === $login) {
                    $logged = 'logged';
                    $_SESSION['user'] = 'logged';
                    $_SESSION['login'] = $login;
                }
            } else {
                $logged = 'No user';
            }
        } else {
            $logged = 'not empty';
        }
        echo $logged;
    }

    public function EditUser()
    {
        if ($_SESSION['user'] !== 'logged') {
            header('Location: /', true, 307);
            die();
        }
        $userModel = new Users();
        if (!empty($_POST['login']) && !empty($_POST['name']) && !empty($_POST['age']) && !empty($_POST['description'])) {
            $routes = explode('/', $_SERVER['REQUEST_URI']);
            $login = $this->clearAll($_POST['login']);
            $paswword = $this->clearAll($_POST['password']);
            $name = $this->clearAll($_POST['name']);
            $age = $this->clearAll($_POST['age']);
            $description = $this->clearAll($_POST['description']);

            $fileUpload = $_FILES;
            if ($fileUpload['file']['size'] === 0) {
                //echo 'file not' . PHP_EOL;
                if ($paswword === '') {
                    $data = ['login' => $login, 'name' => $name, 'age' => $age, 'description' => $description];
                    $userModel->UpdateUser($data, $routes[3]);
                } else {
                    $paswword = $userModel->CryptPasswd($paswword);
                    $data = ['login' => $login, 'password' => $paswword, 'name' => $name, 'age' => $age, 'description' => $description];
                    $userModel->UpdateUser($data, $routes[3]);
                    // echo 'yes' . PHP_EOL;
                }
            } else {
                //echo 'file yes' . PHP_EOL;
                $inImg = $userModel->getUserAvatar($routes[3]);
                if ($userModel->deleteOnlyPhoto($inImg['photo']) === 'delete') {
                    $img_url = $this->uploadImg($fileUpload, $login);
                    if ($img_url !== NULL) {
                        //echo 'new img update' .PHP_EOL;
                        if ($paswword === '') {
                            $data = ['login' => $login, 'name' => $name, 'age' => $age, 'description' => $description, 'img_url' => $img_url];
                            $userModel->UpdateUser($data, $routes[3]);
                        } else {
                            $paswword = $userModel->CryptPasswd($paswword);
                            $data = ['login' => $login, 'password' => $paswword, 'name' => $name, 'age' => $age, 'description' => $description, 'img_url' => $img_url];
                            $userModel->UpdateUser($data, $routes[3]);
                        }
                    }
                } else {
                    echo 'wrong delete file';
                }
            }
        } else {
            echo 'not empty';
        }
    }

    public function RegistrUser()
    {
        $userModel = new Users();
        $login = $this->clearAll($_POST['login']);
        $paswword = $this->clearAll($_POST['password']);
        $password_repeat = $this->clearAll($_POST['password_repeat']);
        $name = $this->clearAll($_POST['name']);
        $age = $this->clearAll($_POST['age']);
        $description = $this->clearAll($_POST['description']);

        if (!empty($login) && !empty($paswword) && !empty($name) && !empty($age) && !empty($description)) {
            if (empty($this->CheckLogin($login))) {
                if ($paswword === $password_repeat) {
                    $fileUpload = $_FILES;
                    $img_url = $this->uploadImg($fileUpload, $login);
                    //var_dump($img_url);
                    if ($img_url !== NULL) {
                        $paswword = $userModel->CryptPasswd($paswword);
                        $data = ['login' => $login, 'password' => $paswword, 'name' => $name, 'age' => $age, 'description' => $description, 'img_url' => $img_url];
                        $userModel->addUserDB($data);
                        echo 'registration';
                        die();
                    }
                } else {
                    echo 'password error';
                }
            } else {
                echo 'error login';
            }
        } else {
            echo 'not empty';
        }
    }

    public function DeleteAvatar()
    {
        $routes = explode('/', $_SERVER['REQUEST_URI']);
        $id = $routes[3];
        $usersView = self::getPD()->prepare('SELECT photo FROM users WHERE id=:id');
        $usersView->execute(['id' => $id]);
        $data = $usersView->fetch(\PDO::FETCH_ASSOC);
        if ($data !== false) {
            if ($this->deleteOnlyPhoto($data['photo']) === 'delete') {
                $usersView = self::getPD()->prepare('UPDATE users SET photo=:photo WHERE id=:id');
                $usersView->execute(['id' => $id, 'photo' => '']);
                $msg = 'Аватарка удалена';
            } else {
                $msg = 'Нет доступа к фото';
            }
        } else {
            $msg = 'Такой аватарки нет';
        }
        return $msg;
    }

    private function deleteOnlyPhoto($photo)
    {
        if (file_exists('photos/' . $photo)) {
            @unlink('photos/' . $photo);
            $msg = 'delete';
        } else {
            $msg = 'no';
        }
        return $msg;
    }

    public function DeleteUser()
    {
        $routes = explode('/', $_SERVER['REQUEST_URI']);
        $id = $routes[3];
        $usersView = self::getPD()->prepare('SELECT login, photo FROM users WHERE id=:id');
        $usersView->execute(['id' => $id]);
        $data = $usersView->fetch(\PDO::FETCH_ASSOC);
        if ($data !== false) {
            if ($this->deleteOnlyPhoto($data['photo']) === 'delete') {
                $usersView = self::getPD()->prepare('DELETE FROM users WHERE id=:id');
                // echo $_SESSION['login'] . ' - ' .$data['login'];
                if ($_SESSION['login'] === $data['login']) {
                    $usersView->execute(['id' => $id]);
                    $msg = 'Вы удалили себя!';
                } else {
                    $usersView->execute(['id' => $id]);
                    $msg = 'Пользователь удален';
                }
            } else {
                $msg = 'Нет доступа к фото';
            }
        } else {
            $msg = 'Такого пользователя нет';
        }
        return $msg;
    }

    public function AllPhoto()
    {
        $data = [];
        $usersView = self::getPD()->prepare('SELECT id, photo FROM users');
        $usersView->execute();
        $datas = $usersView->fetchALL(\PDO::FETCH_ASSOC);
        foreach ($datas as $key => $val) {
            // var_dump($val['photo']);
            if (!empty($val['photo'])) {
                $data[$key] = $val;
            }
        }
        return $data;
    }

    public function AllUser()
    {
        $usersView = self::getPD()->prepare('SELECT login, name, age, description, photo, id FROM users');
        $usersView->execute();
        $data = $usersView->fetchALL(\PDO::FETCH_ASSOC);
        return $data;
    }

    private function addUserDB($data)
    {
        $usersView = self::getPD()->prepare('INSERT INTO users (login, password, name, age, description, photo) VALUES (:login, :password, :name, :age, :description, :photo)');
        $usersView->execute(['login' => $data['login'], 'password' => $data['password'], 'name' => $data['name'], 'age' => $data['age'], 'description' => $data['description'], 'photo' => $data['img_url']]);
    }

    private function UpdateUser($data, $id)
    {
        if (array_key_exists('img_url', $data)) {
            if (array_key_exists('password', $data)) {
                //echo "Массив содержит 'password' & 'img_url' .";
                $usersView = self::getPD()->prepare('UPDATE users SET login=:login, password=:password, name=:name, age=:age, description=:description, photo=:photo WHERE id=:id');
                $usersView->execute(['id' => $id, 'login' => $data['login'], 'password' => $data['password'], 'name' => $data['name'], 'age' => $data['age'], 'description' => $data['description'], 'photo' => $data['img_url']]);
            } else {
                //echo "Массив не содержит 'password' но содержит 'img_url' .";
                $usersView = self::getPD()->prepare('UPDATE users SET login=:login, name=:name, age=:age, description=:description, photo=:photo WHERE id=:id');
                $usersView->execute(['id' => $id, 'login' => $data['login'], 'name' => $data['name'], 'age' => $data['age'], 'description' => $data['description'], 'photo' => $data['img_url']]);
            }
        } else {
            if (array_key_exists('password', $data)) {
                //echo "Массив содержит 'password' и не содержит 'img_url' .";
                $usersView = self::getPD()->prepare('UPDATE users SET login=:login, password=:password, name=:name, age=:age, description=:description WHERE id=:id');
                $usersView->execute(['id' => $id, 'login' => $data['login'], 'password' => $data['password'], 'name' => $data['name'], 'age' => $data['age'], 'description' => $data['description']]);
            } else {
                //echo "Массив не содержит 'password' и не содержит 'img_url' .";
                $usersView = self::getPD()->prepare('UPDATE users SET login=:login, name=:name, age=:age, description=:description WHERE id=:id');
                $usersView->execute(['id' => $id, 'login' => $data['login'], 'name' => $data['name'], 'age' => $data['age'], 'description' => $data['description']]);
            }
        }
        echo 'data update';
    }

    private function AutentificationUser($password)
    {
        $usersView = self::getPD()->prepare('SELECT login FROM users WHERE password=:password');
        $usersView->execute(['password' => $password]);
        $data = $usersView->fetch(\PDO::FETCH_ASSOC);
        return $data;
    }

    private function CryptPasswd($data)
    {
        $passuser = crypt($data, '$6$rounds=5458$yopta23GDs43yopta$');
        return $passuser;
    }
}
