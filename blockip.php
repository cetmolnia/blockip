<?php

namespace grewi;

class blockip
{
    protected string $currentIp;
    protected string $pathFiles;
    protected array $ipList;

    public function __construct()
    {
        $this->pathFiles = __DIR__ . '/files';
        $this->ipList = [];
        $this->currentIp();
        $this->setFiles();
    }

    /**
     * Автоматически определить ip запроса
     * @return blockip
     */
    public function currentIp(): static
    {
        if (filter_var(@$_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
            $ip = @$_SERVER['HTTP_CLIENT_IP'];
        } elseif (filter_var(@$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP)) {
            $ip = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = @$_SERVER['REMOTE_ADDR'];
        }
        $this->currentIp = $ip;
        return $this;
    }

    /**
     * Вкручную указать ip запроса
     * @param string $ip
     * @return blockip
     */
    public function setIp(string $ip): static
    {
        $this->currentIp = $ip;
        return $this;
    }

    /**
     * Загрузить список ip текстом с разделением через запятую
     * @param string $txt
     * @return blockip
     */
    public function setIpList(string $txt)
    {
        $l = explode(',', $txt);
        foreach ($l as &$i) {
            $i = trim($i);
        }
        $this->ipList = array_merge($this->ipList, $l);
        return $this;
    }

    /**
     * Загрузить из файлов в указанной директории
     * @return blockip
     */
    private function setFiles()
    {
        $d = scandir($this->pathFiles);
        foreach ($d as $i) {
            if ($i == '.' || $i == '..') {
                continue;
            }
            $this->setIpList(file_get_contents($this->pathFiles . '/' . $i));
        }
        return $this;
    }


    /**
     * Указать свою директорию с файлами txt
     * @param string $path
     * @return void
     */
    public function setDirFiles(string $path)
    {
        $this->pathFiles = $path;
    }


    /**
     * Функция проверки ip
     * @param string $addr
     * @param array $cidrs
     * @return bool
     */
    private function test(string $addr, array $cidrs): bool
    {
        foreach ($cidrs as $cidr) {

            if (strpos($cidr, "/")) {

                // Для записей типа 82.208.77.243/32
                list($ip, $mask) = explode("/", $cidr, 2);

                if (strpos(".", $mask)) {
                    $mask = 0xffffffff & ip2long($mask);
                } else {
                    $mask = 0xffffffff << 32 - (int) $mask;
                }

                if ((ip2long($addr) & $mask) == (ip2long($ip) & $mask)) {
                    return true;
                }

            } else if (strpos($cidr, "-")) {

                // Для записей типа 82.208.77.243-85.95.168.249
                list($ip_1, $ip_2) = explode("-", $cidr, 2);
                if (((ip2long($ip_2) > ip2long($ip_1)) && (((ip2long($addr) - ip2long($ip_1)) >= 0) && ((ip2long($ip_2) - ip2long($addr)) >= 0))) || ((ip2long($ip_2) < ip2long($ip_1)) && (((ip2long($addr) - ip2long($ip_1)) <= 0) && ((ip2long($ip_2) - ip2long($addr)) <= 0))) || ((ip2long($ip_1) == ip2long($ip_2)) && (ip2long($ip_1) == ip2long($addr)))) {
                    return true;
                }
            } else if ($addr === $cidr) {
                return true; // Для одиночных IP
            }

        }
        return false;
    }

    /**
     * Поиск ip в списке 
     * @return bool
     */
    public function start(): bool
    {
        $this->setFiles();
        if ($this->test($this->currentIp, $this->ipList)) {
            return true;
        }
        return false;
    }

    public function control()
    {
        if($this->start()){
            http_response_code(403);
            exit('Запрос заблокирован! <a href="/contacts">Если произошла ошибка обратитесь к администратору ресурса</a>');
        }
    }
}