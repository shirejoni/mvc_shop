<?php


namespace App\lib;


class Response
{
    private $outPut;

    /**
     * @return mixed
     */
    public function getOutPut()
    {
        return $this->outPut;
    }

    /**
     * @param mixed $outPut
     */
    public function setOutPut($outPut): void
    {
        $this->outPut = $outPut;
    }

    public function outPut() {
        echo $this->outPut;
    }

}