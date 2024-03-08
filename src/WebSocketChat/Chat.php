<?php

namespace App\WebSocketChat;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use SplObjectStorage;

class Chat implements MessageComponentInterface {

    protected $clients;

    public function __construct() {
        $this->clients = new SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);
    
        echo "New connection! ({$conn->resourceId})\n";
    }

    // public function onMessage(ConnectionInterface $from, $msg) {
    //     foreach ($this->clients as $client) {
    //         if ($from !== $client) {
    //             // The sender is not the receiver, send to each client connected
    //             $client->send($msg);
    //         }
    //     }
    // }

    public function onMessage(ConnectionInterface $from, $msg) {
        foreach ($this->clients as $client) {
            // Send the message to all clients
            $client->send($msg);
        }
    }

    // public function onClose(ConnectionInterface $conn) {
    //     // The connection is closed, remove it
    //     $this->clients->detach($conn);
    
    //     echo "Connection {$conn->resourceId} has disconnected\n";
    // }

    public function onClose(ConnectionInterface $conn) {
    // Detach the closed connection
    $this->clients->detach($conn);
    echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}