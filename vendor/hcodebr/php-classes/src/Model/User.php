<?php

namespace Hcode\Model;

use \Hcode\DB\sql;
use \Hcode\Mailer;
use \Hcode\Model;

class User extends Model
{
    const SESSION = "User";

    public static function login($login, $password)
    {
        $sql = new sql();

        $results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN", array(':LOGIN' => $login,
        ));

        if (count($results) === 0) {

            throw new \Exception("Usuário inexistente ou senha inválida!");
        }

        $data = $results[0];

        if (password_verify($password, $data["despassword"]) === true) {

            $user = new User();

            $user->setData($data);

            $_SESSION[User::SESSION] = $user->getValues();

            return $user;

            exit;

        } else {

            throw new \Exception("Usuário inexistente ou senha inválida!");

        }
    }

    public static function verifyLogin($inadmin = true)
    {

        if
        (
            !isset($_SESSION[User::SESSION])
            ||
            !$_SESSION[User::SESSION]
            ||
            !(int) $_SESSION[User::SESSION]["iduser"] > 0
            ||
            (bool) $_SESSION[User::SESSION]["inadmin"] !== $inadmin
        ) {

            header("Location: /admin/login");

            exit;
        }
    }

    public static function logout()
    {

        $_SESSION[User::SESSION] = null;
    }

    public static function listAll()
    {

        $sql = new Sql();

        return $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson");
    }

    public function save()
    {

        $sql = new Sql();

        $results = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)",
            array(
                ":desperson"   => $this->getdesperson(),
                ":deslogin"    => $this->getdeslogin(),
                ":despassword" => $this->getdespassword(),
                ":desemail"    => $this->getdesemail(),
                ":nrphone"     => $this->getnrphone(),
                ":inadmin"     => $this->getinadmin(),
            ));

        $this->setData($results[0]);
    }

    public function get($iduser)
    //Função para buscar no banco as informações de um usuário para edição.
    {

        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser  = :iduser ",
            array(
                "iduser" => $iduser,
            ));

        $this->setdata($results[0]);
    }

    public function update()
    {
        //Função de alterar usuário no banco

        $sql = new Sql();

        $results = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)",
            array(
                ":iduser"      => $this->getiduser(),
                ":desperson"   => $this->getdesperson(),
                ":deslogin"    => $this->getdeslogin(),
                ":despassword" => $this->getdespassword(),
                ":desemail"    => $this->getdesemail(),
                ":nrphone"     => $this->getnrphone(),
                ":inadmin"     => $this->getinadmin(),
            ));

        $this->setData($results[0]);
    }

    public function delete()
    {

        $sql = new Sql();

        $sql->query("CALL sp_users_delete(:iduser)", array(
            ":iduser" => $this->getiduser(),
        ));
    }

    public static function getForgot($email)
    {
        //Função de recuperar senha

        $sql = new Sql();

        $results = $sql->select("SELECT *
        FROM tb_persons a
        INNER JOIN tb_users b USING(idperson)
        WHERE a.desemail = :email",
            array(
                ":email" => $email,
            ));

        if (count($results) === 0) {

            throw new \Exception("Não foi possível recuperar a senha.");

        } else {

            $data = $results[0];

            $resultsRecovery = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", array(
                ":iduser" => $data["iduser"],
                ":desip"  => $_SERVER["REMOTE_ADDR"],
            ));

            if (count($resultsRecovery) === 0) {

                throw new \Exception("Não foi possivel recuperar a senha");

            } else {

                $dataRecovery = $resultsRecovery[0];

                $iv     = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));
                $code   = openssl_encrypt($dataRecovery['idrecovery'], 'aes-256-cbc', SECRET, 0, $iv);
                $result = base64_encode($iv . $code);
                if ($inadmin === true) {
                    $link = "http://www.evendascentenario.newtechtecnologia.com/admin/forgot/reset?code=$result";
                } else {
                    $link = "http://www.evendascentenario.newtechtecnologia.com/forgot/reset?code=$result";
                }
                $mailer = new Mailer($data['desemail'], $data['desperson'], "Redefinir senha da Hcode Store", "forgot", array(
                    "name" => $data['desperson'],
                    "link" => $link,
                ));
                $mailer->send();
                return $link;

            }
        }
    }
}