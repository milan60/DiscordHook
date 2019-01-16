<?php

/*
 * Originally made By Thunder33345
 * 
 * Edited by milan44
 */
class DiscordHook
{

    public static function send($message, array $curlOpts = [])
    {
        $user = $message->getUser();
        $url = $user->getUrl();
        $username = $user->getUsername();
        $avatar_url = $user->getAvatarUrl();
        $content = $message->getContent();
        
        $data = [];
        if (! is_null($username))
        {
            $data["username"] = $username;
        }
        if (! is_null($avatar_url))
        {
            $data["avatar_url"] = $avatar_url;
        }
        if (! is_null($content))
        {
            $data["content"] = $content;
        }
        
        if ($message->getMode() == "embed")
        {
            if ($message->hasEmbed())
            {
                foreach ($message->getEmbeds() as $embed)
                {
                    $data["embeds"][] = $embed->getAsArray();
                }
            }
            $send = json_encode($data);
        }
        elseif ($message->getMode() == "upload")
        {
            $data['file'] = curl_file_create($message->getUpload()->getFile(), null, $message->getUpload()->getName());
            $send = $data;
        }
        else
        {
            $send = json_encode($data);
        }
        
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1);
        if ($message->getMode() == "upload")
        {
            curl_setopt($curl, CURLOPT_HTTPHEADER, [
                'Content-Type: multipart/form-data'
            ]);
        }
        else
        {
            curl_setopt($curl, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $send);
        curl_setopt_array($curl, $curlOpts);
        
        $raw = curl_exec($curl);
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $headers = self::phraseHeaders(substr($raw, 0, $header_size));
        $raw = substr($raw, $header_size);
        
        $json = @json_decode($raw, true);
        
        $info["sent"]["array"] = $data;
        $info["sent"]["json"] = $send;
        
        $info["http_code"] = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if (curl_errno($curl))
        {
            $errorcode = curl_errno($curl);
            $info["curl_code"] = $errorcode;
            $info["curl_reason"] = curl_strerror($errorcode);
        }
        $info["headers"] = $headers;
        $info["json"] = $json;
        $info["raw"] = $raw;
        return $info;
    }

    private static function phraseHeaders($response)
    {
        $headers = [];
        foreach (explode("\r\n", substr($response, 0, strpos($response, "\r\n\r\n"))) as $i => $line)
            if ($i === 0)
            {
                $headers['http_code'] = $line;
            }
            else
            {
                list ($key, $value) = explode(': ', $line);
                $key = strtolower(str_replace("-", '_', $key));
                $headers[$key] = $value;
            }
        return $headers;
    }
}

class Message
{

    /** @var User */
    private $user;

    /** @var String */
    private $content;

    private $mode = 'null';

    /** @var Upload */
    private $file;

    /** @var Embed[] */
    private $embeds;

    public function __construct($user, $content, $extension = null)
    {
        $this->user = $user;
        $this->content = $content;
        if (! is_null($extension))
        {
            if ($extension instanceof Upload)
            {
                $this->file = $extension;
                $this->mode = "upload";
            }
            elseif (is_array($extension))
            {
                foreach ($extension as $embed)
                {
                    if ($embed instanceof Embed)
                    {
                        $this->embeds[] = $embed;
                    }
                }
                if (count($this->embeds) >= 1)
                {
                    $this->mode = "embed";
                }
            }
        }
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    public function getMode()
    {
        return $this->mode;
    }

    public function getUpload()
    {
        return $this->file;
    }

    public function hasEmbed()
    {
        if (! is_null($this->embeds) and ($this->embeds[0] instanceof Embed) and count($this->embeds) >= 1)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function getEmbeds()
    {
        return $this->embeds;
    }
}

class User
{

    private $url = null;

    private $username = null;

    private $avatar_url = null;

    public function __construct($url, $username = null, $avarar_url = null)
    {
        $this->url = $url;
        $this->username = $username;
        $this->avatar_url = $avarar_url;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    public function getAvatarUrl()
    {
        return $this->avatar_url;
    }

    public function setAvatarUrl($avatar_url)
    {
        $this->avatar_url = $avatar_url;
        return $this;
    }
}

class Upload
{

    private $file;

    private $name;

    public function __construct($filePath, $name = null)
    {
        $this->file = realpath($filePath);
        if ($name == null)
        {
            $this->name = basename($filePath);
        }
        else
        {
            $this->name = $name;
        }
    }

    public function getFile()
    {
        return $this->file;
    }

    public function getName()
    {
        return $this->name;
    }
}

class Embed
{

    private $title;

    private $descrption;

    private $url;

    private $color;

    // todo work on extras(added only as placeholder)
    private $thumbnail;

    private $video;

    private $image;

    private $providor;

    private $author;

    private $footer;

    private $field;

    private $attatchment;

    public function __construct($title = null, $description = null, $url = null, $color = null)
    {
        $this->title = $title;
        $this->descrption = $description;
        $this->url = $url;
        $this->color = $color;
        return $this;
    }

    public function __get($name)
    {
        $name = strtolower($name);
        if (property_exists($this, $name))
        {
            return $this->{$name};
        }
        return null;
    }

    public function __set($name, $value)
    {
        $name = strtolower($name);
        $uname = ucfirst($name);
        if (method_exists($this, "set" . $uname))
        {
            return $this->{"set" . $uname}($value);
        }
        if (property_exists($this, $name))
        {
            $this->{$name} = $value;
        }
        return $this;
    }

    public function getAsArray()
    {
        $array = [];
        if (! is_null($this->title))
        {
            $array["title"] = $this->title;
        }
        if (! is_null($this->descrption))
        {
            $array["description"] = $this->descrption;
        }
        if (! is_null($this->url))
        {
            $array["url"] = $this->url;
        }
        if (! is_null($this->color))
        {
            $array["color"] = $this->color;
        }
        
        if (! is_null($this->thumbnail))
        {
            $array["thumbnail"] = $this->thumbnail;
        }
        if (! is_null($this->author))
        {
            $array["author"] = $this->author;
        }
        
        return $array;
    }
}
