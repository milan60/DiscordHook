<?php

/*
 * Originally made By Thunder33345
 *
 * Edited by milan44
 */
class DiscordHook
{

    /**
     * This function sends the specified message.
     *
     * @param Message $message
     * @param array $curlOpts
     */
    public static function send($message, $curlOpts = [])
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

    /**
     *
     * @var User
     */
    private $user;

    /**
     *
     * @var String
     */
    private $content;

    /**
     *
     * @var String
     */
    private $mode = 'null';

    /**
     *
     * @var Upload
     */
    private $file;

    /**
     *
     * @var Embed[]
     */
    private $embeds;

    /**
     * Creates a Message
     *
     * @param User $user
     *            User which will be used to send the Message
     * @param String $content
     *            Message Content
     * @param mixed $extension
     *            Can be an Embed for example
     */
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

    /**
     * Returns the User
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Sets the User
     *
     * @param User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Returns the Content
     *
     * @return String
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Sets the Content
     *
     * @param String $user
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Returns the mode
     *
     * @return String
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Returns the Upload
     *
     * @return Upload
     */
    public function getUpload()
    {
        return $this->file;
    }

    /**
     * Returns true if Message has embeds
     *
     * @return boolean
     */
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

    /**
     * Returns the Embeds
     *
     * @return Embeds[]
     */
    public function getEmbeds()
    {
        return $this->embeds;
    }
}

class User
{

    /**
     *
     * @var String
     */
    private $url = null;

    /**
     *
     * @var String
     */
    private $username = null;

    /**
     *
     * @var String
     */
    private $avatar_url = null;

    /**
     * Creates an User
     *
     * @param String $url
     *            The Webhook Url
     * @param String $username
     *            The Username or null if the username should not be changed (Standard is null)
     * @param String $avarar_url
     *            The Avatar Url or null if the Avatar Url should not be changed (Standard is null)
     */
    public function __construct($url, $username = null, $avarar_url = null)
    {
        $this->url = $url;
        $this->username = $username;
        $this->avatar_url = $avarar_url;
    }

    /**
     * Returns the Webhook Url
     *
     * @return String
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Sets the Webhook Url
     *
     * @param String $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Returns the Username (if set)
     *
     * @return String
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Sets the Username
     *
     * @param String $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * Returns the Avatar Url (if set)
     *
     * @return String
     */
    public function getAvatarUrl()
    {
        return $this->avatar_url;
    }

    /**
     * Sets the Avatar Url
     *
     * @param String $avatar_url
     */
    public function setAvatarUrl($avatar_url)
    {
        $this->avatar_url = $avatar_url;
        return $this;
    }
}

class Upload
{

    /**
     *
     * @var String
     */
    private $file;

    /**
     *
     * @var String
     */
    private $name;

    /**
     * Creates an Upload
     *
     * @param String $filePath
     *            The path to the file that should be used (relative to current directory or absolute)
     * @param String $name
     *            The Filename that should be used or null (Standard is null)
     */
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

    /**
     * Returns the Filepath
     *
     * @return String
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Returns the Filename
     *
     * @return String
     */
    public function getName()
    {
        return $this->name;
    }
}

class Embed
{

    /**
     *
     * @var String
     */
    private $title;

    /**
     *
     * @var String
     */
    private $description;

    /**
     *
     * @var String
     */
    private $url;

    /**
     *
     * @var int
     */
    private $color;

    /**
     *
     * @var Thumbnail
     */
    private $thumbnail;

    /**
     *
     * @var Video
     */
    private $video;

    /**
     *
     * @var Image
     */
    private $image;

    /**
     *
     * @var String
     */
    private $author;

    /**
     * @var String
     */
    private $timestamp;
    
    /**
     *
     * @var Footer
     */
    private $footer;

    /**
     *
     * @var Field[]
     */
    private $field = [];

    /**
     * Creates an Embed
     *
     * @param String $title
     * @param String $description
     * @param String $url
     * @param String $color
     */
    public function __construct($title = null, $description = null, $url = null, $color = null)
    {
        $this->title = $title;
        $this->description = $description;
        $this->url = $url;
        $this->setColor($color);
        return $this;
    }

    /**
     * Returns the title
     *
     * @return String
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Returns the description
     *
     * @return String
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Returns the url
     *
     * @return String
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Returns the color
     *
     * @return int
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Returns the thumbnail
     *
     * @return Thumbnail
     */
    public function getThumbnail()
    {
        return $this->thumbnail;
    }

    /**
     * Returns the video
     *
     * @return Video
     */
    public function getVideo()
    {
        return $this->video;
    }

    /**
     * Returns the image
     *
     * @return Image
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Returns the author
     *
     * @return String
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Returns the footer
     *
     * @return Footer
     */
    public function getFooter()
    {
        return $this->footer;
    }

    /**
     * Returns all fields
     *
     * @return Field[]
     */
    public function getFields()
    {
        return $this->fields;
    }
    
    /**
     * Sets the timestamp
     *
     * @param int $timestamp
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = date("c", $timestamp);
    }

    /**
     * Sets the title
     *
     * @param String $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Sets the description
     *
     * @param String $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Sets the url
     *
     * @param String $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Sets the color (Format: #a5a5a5)
     *
     * @param String $color
     */
    public function setColor($color)
    {
        $this->color = hexdec(ltrim($color, "#"));
    }

    /**
     * Sets the thumbnail
     *
     * @param Thumbnail $thumbnail
     */
    public function setThumbnail($thumbnail)
    {
        $this->thumbnail = $thumbnail;
    }

    /**
     * Sets the video
     *
     * @param Video $video
     */
    public function setVideo($video)
    {
        $this->video = $video;
    }

    /**
     * Sets the image
     *
     * @param Image $image
     */
    public function setImage($image)
    {
        $this->image = $image;
    }

    /**
     * Sets the author
     *
     * @param String $author
     */
    public function setAuthor($author)
    {
        $this->author = $author;
    }

    /**
     * Sets the footer
     *
     * @param Footer $footer
     */
    public function setFooter($footer)
    {
        $this->footer = $footer;
    }

    /**
     * Adds a field
     *
     * @param Field $field
     */
    public function addField($field)
    {
        $this->fields[] = $field;
    }

    /**
     * Returns all properties as an array
     *
     * @return array
     */
    public function getAsArray()
    {
        $array = [];
        if (! is_null($this->title))
        {
            $array["title"] = $this->title;
        }
        if (! is_null($this->description))
        {
            $array["description"] = $this->description;
        }
        if (! is_null($this->url))
        {
            $array["url"] = $this->url;
        }
        if (! is_null($this->color))
        {
            $array["color"] = $this->color;
        }
        if (! is_null($this->author))
        {
            $array["author"] = $this->author;
        }
        if (! is_null($this->timestamp))
        {
            $array["timestamp"] = $this->timestamp;
        }
        if ($this->footer instanceof Footer && !$this->footer->isEmpty())
        {
            $array["footer"] = [];
            if (!is_null($this->footer->getIconUrl()))
            {
                $array["footer"]["icon_url"] = $this->footer->getIconUrl();
            }
            if (!is_null($this->footer->getText()))
            {
                $array["footer"]["text"] = $this->footer->getText();
            }
        }
        if ($this->author instanceof Author && !$this->author->isEmpty())
        {
            $array["author"] = $this->author->getAsArray();
        }
        if ($this->thumbnail instanceof Thumbnail && !$this->thumbnail->isEmpty())
        {
            $array["thumbnail"] = $this->thumbnail->getAsArray();
        }
        if ($this->video instanceof Video && !$this->video->isEmpty())
        {
            $array["video"] = $this->video->getAsArray();
        }
        if ($this->image instanceof Image && !$this->image->isEmpty())
        {
            $array["image"] = $this->image->getAsArray();
        }
        
        if (is_array($this->fields) && sizeof($this->fields) >= 1)
        {
            foreach($this->fields as $field)
            {
                if (!$field->isEmpty())
                {
                    if (!isset($array["fields"]))
                    {
                        $array["fields"] = [];
                    }
                    $array["fields"][] = $field->getAsArray();
                }
            }
        }
        
        return $array;
    }
}

class Author
{
    /**
     * @var String
     */
    private $name;
    
    /**
     * @var String
     */
    private $icon_url;
    
    /**
     * @var String
     */
    private $url;
    
    /**
     * Creates an Author Object
     * 
     * @param String $name
     * @param String $url
     * @param String $icon_url
     */
    public function __construct($name = null, $url = null, $icon_url = null)
    {
        $this->name = $name;
        $this->url = $url;
        $this->icon_url = $icon_url;
    }
    
    /**
     * Returns true if the Author Object is empty
     *
     * @return Boolean
     */
    public function isEmpty()
    {
        return is_null($this->url) && is_null($this->name) && is_null($this->icon_url);
    }
    
    /**
     * Returns the Author Object as an array
     *
     * @return array
     */
    public function getAsArray()
    {
        $array = [];
        
        if (!is_null($this->url))
        {
            $array["url"] = $this->url;
        }
        if (!is_null($this->name))
        {
            $array["name"] = $this->name;
        }
        if (!is_null($this->icon_url))
        {
            $array["icon_url"] = $this->icon_url;
        }
        
        return $array;
    }
}

class Media
{
    /**
     * @var String
     */
    private $type;
    
    /**
     * @var String
     */
    private $url;
    
    /**
     * @var int
     */
    private $height;
    
    /**
     * @var int
     */
    private $width;
    
    /**
     * Creates a Media Object which can be an Image, Video, or Thumbnail
     */
    private function __construct()
    {
        
    }
    
    /**
     * Returns true if the Media Object is empty
     * 
     * @return Boolean
     */
    public function isEmpty()
    {
        return is_null($this->url) && is_null($this->type);
    }
    
    /**
     * Returns the Media Object as an array
     * 
     * @return array
     */
    public function getAsArray()
    {
        $array = [];
        
        if (!is_null($this->url))
        {
            $array["url"] = $this->url;
        }
        if (!is_null($this->width))
        {
            $array["width"] = $this->width;
        }
        if (!is_null($this->height))
        {
            $array["height"] = $this->height;
        }
        
        return $array;
    }
    
    /**
     * Returns the type
     * 
     * @return String
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns the url
     * 
     * @return String
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Returns the height
     * 
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Returns the width
     * 
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Sets the Url
     * 
     * @param String $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Sets the height
     * 
     * @param int $height
     */
    public function setHeight($height)
    {
        $this->height = $height;
    }

    /**
     * Sets the width
     * 
     * @param int $width
     */
    public function setWidth($width)
    {
        $this->width = $width;
    }

}

class Image extends Media
{
    /**
     * Creates an Image
     * 
     * @param String $url
     * @param int $width
     * @param int $height
     */
    private function __construct($url, $width = null, $height = null)
    {
        $this->type = "image";
        $this->url = $url;
        $this->width = $width;
        $this->height = $height;
    }
}
class Video extends Media
{
    /**
     * Creates a Video
     * 
     * @param String $url
     * @param int $width
     * @param int $height
     */
    private function __construct($url, $width = null, $height = null)
    {
        $this->type = "video";
        $this->url = $url;
        $this->width = $width;
        $this->height = $height;
    }
}
class Thumbnail extends Media
{
    /**
     * Creates a Thumbnail
     * 
     * @param String $url
     * @param int $width
     * @param int $height
     */
    private function __construct($url, $width = null, $height = null)
    {
        $this->type = "thumbnail";
        $this->url = $url;
        $this->width = $width;
        $this->height = $height;
    }
}

class Field
{
    /**
     * @var String
     */
    private $name;

    /**
     * @var String
     */
    private $value;
    
    /**
     * Creates a Field
     * 
     * @param String $name
     * @param String $value
     */
    public function __construct($name = null, $value = null)
    {
        $this->name = $name;
        $this->value = $value;
    }
    
    /**
     * Returns the Field Object as an array
     *
     * @return array
     */
    public function getAsArray()
    {
        $array = [];
        
        if (!is_null($this->name))
        {
            $array["name"] = $this->name;
        }
        if (!is_null($this->value))
        {
            $array["value"] = $this->value;
        }
        
        return $array;
    }
    
    /**
     * Returns true if this Field is empty
     * 
     * @return Boolean
     */
    public function isEmpty()
    {
        return is_null($this->name) && is_null($this->value);
    }
    
    /**
     * Returns the Name
     * 
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the Value
     * 
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Sets the Name
     * 
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Sets the Value
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

}

class Footer
{

    /**
     *
     * @var String
     */
    private $icon_url;

    /**
     *
     * @var String
     */
    private $text;

    /**
     * Creates a Footer (used in Embeds)
     *
     * @param String $icon_url
     * @param String $text
     */
    public function __construct($icon_url = null, $text = null)
    {
        $this->icon_url = $icon_url;
        $this->text = $text;
    }
    
    /**
     * Returns true if footer is empty
     * 
     * @return Boolean
     */
    public function isEmpty()
    {
        return is_null($this->icon_url) && is_null($this->text);
    }

    /**
     * Returns the Icon Url
     *
     * @return String
     */
    public function getIconUrl()
    {
        return $this->icon_url;
    }

    /**
     * Returns the text
     *
     * @return String
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Sets the Icon Url
     *
     * @param String $icon_url
     */
    public function setIconUrl($icon_url)
    {
        $this->icon_url = $icon_url;
    }

    /**
     * Sets the text
     *
     * @param String $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }
}
