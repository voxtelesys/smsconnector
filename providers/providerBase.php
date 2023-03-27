<?php

namespace FreePBX\modules\Smsconnector\Provider;

abstract class providerBase  
{
    protected $FreePBX;
    protected $Database;
    protected $Config;
    protected $Sipsettings;

    protected $name       = '';
    protected $nameRaw    = '';
    protected $configInfo = array();

    public function __construct()
    {
        $this->FreePBX      = \FreePBX::create();
        $this->Database     = $this->FreePBX->Database;
        $this->Config       = $this->FreePBX->Config;
        $this->Sipsettings  = $this->FreePBX->Sipsettings;
    }

    public function getName() 
    {
        return $this->name;
    }

    public function getNameRaw()
    {
        return $this->nameRaw;
    }

    public function getConfigInfo()
    {
        return $this->configInfo;
    }

    abstract public function sendMedia($id, $to, $from, $message=null);
    abstract public function sendMessage($id, $to, $from, $message=null);

    public function media_urls($id)
    {
        // Generate media urls
        $media_urls = array();
        
        $ampWebAddress = $this->Config->get_conf_setting('AMPWEBADDRESS');
        if (empty($ampWebAddress))  // We're going to make an educated guess and make an HTTPS URL from the external IP
        {
            $ampWebAddress = $this->Sipsettings->getConfig('externip');
        }
        $sql = 'SELECT id, name FROM sms_media WHERE mid = :mid';
        $stmt = $this->Database->prepare($sql);
        $stmt->bindParam(':mid', $id, \PDO::PARAM_INT);
        $stmt->execute();
        $media = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($media as $media_item)
        {
            $media_urls[] = sprintf('https://%s/smsconn/media.php?id=%s&name=%s', $ampWebAddress, $media_item['id'], $media_item['name']);
        }

        return $media_urls;
    }
    
    /**
     * Set an outbound message to delivered
     * 
     * @param int $id message id
     */
    protected function setDelivered($id)
    {
        if ($id  != "")
        {
            $sql = sprintf('UPDATE %s SET delivered = 1 where id = :id', 'sms_messages');
            $stmt = $this->Database->prepare($sql);
            $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();
        }
    }

    protected function getConfig($name)
    {
        return $this->FreePBX->Smsconnector->getProviderConfig($name);
    }
}