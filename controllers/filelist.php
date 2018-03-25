<?php

namespace App;

class Filelist extends MainController
{
    public function index()
    {
        $this->CheckSession();

        $userInfo = new Users();
        $dataUser = $userInfo->getUserInfo();
        $dataPage['user'] = $dataUser;

        $data = $userInfo->AllPhoto();
        $dataPage['page'] = 'filelist';
        $this->view->render('filelist', $data, $dataPage);
    }
    public function delete()
    {
        $this->CheckSession();

        $userInfo = new Users();
        $dataUser = $userInfo->getUserInfo();
        $dataPage['user'] = $dataUser;

        $data = $userInfo->DeleteAvatar();
        $dataPage['page'] = 'userlist';
        $datas['msg'] = $data;
        $this->view->render('userdelete', $datas, $dataPage);
    }
}