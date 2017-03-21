<?php

class EndPointFactory {

    public static function getEndPoint($name) {
        $end_point = null;
        switch ($name) {
            case \EndPoint::AUTHNET:
                $end_point = new \AuthNet();
                break;

            case \EndPoint::PAYPAL:
                $end_point = new \Paypal();
                break;

            case \EndPoint::FEDEX_US:
                $end_point = new \FedexUS();
                break;

            case \EndPoint::FEDEX_WW:
                $end_point = new \FedexGlobal();
                break;

            case \EndPoint::TEST:
                $end_point = new \TestEndPoint();
                break;

            default:
                throw new Exception("Factory does know of end point: {$name}");
                break;
        }

        return $end_point;
    }
}
