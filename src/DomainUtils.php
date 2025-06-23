<?php
class DomainUtils
{
    function splitdomain($myDomain)
    {
        $myDomain = strtolower($myDomain);
        if (preg_match("/^(([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]).){3}([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/", $myDomain)) {
            $isIP = true;
            $domain = $myDomain;
            $subdomain = "";
            $shortdomain = $myDomain;
            $subdomain = "";
            $ext = "";
        } else {
            $isIP = false;
            $splithost = explode('.', strip_tags($myDomain));
            $numparts = count($splithost);
            //$reqHost=implode('.',array_slice($splithost,1));
            switch ($numparts) {
                case 0:
                    return array("domain" => $myDomain, "ext" => "", "subdomain" => "");
                    break;
                case 1:
                    return array("domain" => $myDomain, "ext" => "", "subdomain" => "");
                    break;
                default:
                    $ext = $splithost[$numparts - 1];
                    $domain = $splithost[$numparts - 2] . "." . $ext;
                    $shortdomain = $splithost[$numparts - 2];
                    //$subdomain=$splithost[0];
                    $subdomain = implode(".", array_splice($splithost, 0, $numparts - 2));
                    $reqHost = implode('.', array_slice($splithost, 1));
            }
        }
        return array("domain" => $domain, "ext" => $ext, "subdomain" => $subdomain, "shortdomain" => $shortdomain, "isIP" => $isIP);
    }
    //need crediting
    function validIP($ip)
    {
        if (preg_match("^([1-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(\.([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3}^", $ip))
            return true;
        else
            return false;
    }
    function getDomain()
    {
        //get host
        if (isset($_SERVER)) {
            $thesite = $thesite ?? strtolower(@$_SERVER['HTTP_HOST']);
        } else {
            $thesite = $thesite ?? "comingsoon.com";
        }

        $thesite = preg_replace('/^www\./', '', $thesite);
        $splithost = $this->splitdomain($thesite);
        $domain = $splithost["domain"];
        $config['host'] = $domain;
        return $domain;
    }
    function getPath()
    {
        $path = @explode('/', $_SERVER['REQUEST_URI']);
        return $path;
    }
}
