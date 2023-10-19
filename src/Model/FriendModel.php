<?php

namespace D4rk0snet\CoralOrder\Model;

class FriendModel implements \JsonSerializable
{
    /** @required */
    private string $friendFirstname;
    /** @required */
    private string $friendLastname;
    /** @required */
    private string $friendEmail;

    public function getFriendFirstname(): string
    {
        return $this->friendFirstname;
    }

    public function setFriendFirstname(string $friendFirstname): FriendModel
    {
        $this->friendFirstname = $friendFirstname;
        return $this;
    }

    public function getFriendLastname(): string
    {
        return $this->friendLastname;
    }

    public function setFriendLastname(string $friendLastname): FriendModel
    {
        $this->friendLastname = $friendLastname;
        return $this;
    }

    public function getFriendEmail(): string
    {
        return $this->friendEmail;
    }

    public function setFriendEmail(string $friendEmail): FriendModel
    {
        $this->friendEmail = $friendEmail;
        return $this;
    }

    public function jsonSerialize() : array
    {
        return [
            'friendFirstname' => $this->getFriendFirstname(),
            'friendLastname' => $this->getFriendLastname(),
            'friendEmail' => $this->getFriendEmail(),
        ];
    }
}