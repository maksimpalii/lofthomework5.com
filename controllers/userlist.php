<?php

namespace App;

class Userlist extends MainController
{

    public function index()
    {
        $this->CheckSession();

        $userInfo = new Users();
        $dataUser = $userInfo->getUserInfo();
        $dataPage['user'] = $dataUser;

        $data = $userInfo->AllUser();
        $dataPage['page'] = 'userlist';
        $this->view->render('userlist', $data, $dataPage);
    }

    public function delete()
    {
        $this->CheckSession();

        $userInfo = new Users();
        $dataUser = $userInfo->getUserInfo();
        $dataPage['user'] = $dataUser;

        $data = $userInfo->DeleteUser();
        if ($data === 'Вы удалили себя!') {
            unset($_SESSION["user"]);
            unset($_SESSION["login"]);
            session_destroy();
        }
        $dataPage['page'] = 'userlist';
        $datas['msg'] = $data;
        $this->view->render('userdelete', $datas, $dataPage);
    }

    public function edit()
    {
        $this->CheckSession();

        $userInfo = new Users();
        $dataUser = $userInfo->getUserInfo();
        $dataPage['user'] = $dataUser;

        $datas = $userInfo->GetUser();
        $dataPage['page'] = 'userlist';
        $this->view->render('useredit', $datas, $dataPage);
    }

    public function post()
    {
        $this->CheckSession();

        $editUser = new Users();
        $editUser->EditUser();
    }
}