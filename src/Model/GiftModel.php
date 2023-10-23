<?php

namespace D4rk0snet\CoralOrder\Model;

use DateTime;

class GiftModel implements \JsonSerializable
{
    /** @var FriendModel[] */
    private array $friends = [];
    private ?DateTime $sendOn = null;
    private ?string $message = null;
    private bool $sendToFriend;

    /**
     * @return \D4rk0snet\CoralOrder\Model\FriendModel[]
     */
    public function getFriends(): array
    {
        return $this->friends;
    }

    /**
     * @param \D4rk0snet\CoralOrder\Model\FriendModel[]
     */
    public function setFriends(array $friends): GiftModel
    {
        $this->friends = $friends;
        return $this;
    }

    public function getSendOn(): ?DateTime
    {
        return $this->sendOn;
    }

    public function setSendOn(?DateTime $sendOn): GiftModel
    {
        $this->sendOn = $sendOn;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): GiftModel
    {
        $this->message = $message;
        return $this;
    }

    public function isSendToFriend(): bool
    {
        return $this->sendToFriend;
    }

    public function setSendToFriend(bool $sendToFriend): GiftModel
    {
        $this->sendToFriend = $sendToFriend;
        return $this;
    }

    public function jsonSerialize() : array
    {
        $returnedValues = [
            'send_on' => $this->getSendOn(),
            //'message' => $this->getMessage(),
            'sendToFriend' => $this->isSendToFriend()
        ];

        foreach($this->getFriends() as $friend) {
            $returnedValues['friends'][] = $friend->jsonSerialize();
        }

        return $returnedValues;
    }
}