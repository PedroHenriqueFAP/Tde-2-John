<?php
// OBSERVER

interface Observer {
    public function update($data);
}

class Subject {
    private $observers = [];

    public function subscribe(Observer $observer) {
        $this->observers[] = $observer;
    }

    public function unsubscribe(Observer $observer) {
        $this->observers = array_filter(
            $this->observers,
            fn($obs) => $obs !== $observer
        );
    }

    public function notify($data) {
        foreach ($this->observers as $observer) {
            $observer->update($data);
        }
    }
}


// OBSERVERS

class EmailObserver implements Observer {
    public function update($data) {
        echo "\033[34m Email enviado para pedido {$data['id']}\033[0m\n";
    }
}

class EstoqueObserver implements Observer {
    public function update($data) {
        echo "\033[33m Estoque atualizado para pedido {$data['id']}\033[0m\n";
    }
}

class LogObserver implements Observer {
    public function update($data) {
        echo "\033[32m Log registra1do: Pedido {$data['id']} criado\033[0m\n";
    }
}


// REPOSITÓRIO DE PEDIDOS

class RepositorioPedidos {
    private $arquivo = "pedidos.txt";

    public function salvar($pedido) {
        file_put_contents($this->arquivo, json_encode($pedido) . PHP_EOL, FILE_APPEND);
    }

    public function listar() {
        if (!file_exists($this->arquivo)) return [];
        $linhas = file($this->arquivo);
        return array_map(fn($linha) => json_decode($linha, true), $linhas);
    }

    public function buscarPorId($id) {
        foreach ($this->listar() as $pedido) {
            if ($pedido['id'] == $id) return $pedido;
        }
        return null;
    }

    public function atualizarStatus($id, $novoStatus) {
        $pedidos = $this->listar();
        $novoConteudo = "";
        foreach ($pedidos as $pedido) {
            if ($pedido['id'] == $id) {
                $pedido['status'] = $novoStatus;
            }
            $novoConteudo .= json_encode($pedido) . PHP_EOL;
        }
        file_put_contents($this->arquivo, $novoConteudo);
    }

    public function remover($id) {
        $pedidos = $this->listar();
        $novoConteudo = "";
        foreach ($pedidos as $pedido) {
            if ($pedido['id'] != $id) {
                $novoConteudo .= json_encode($pedido) . PHP_EOL;
            }
        }
        file_put_contents($this->arquivo, $novoConteudo);
    }
}


// SISTEMA DE PEDIDOS

class SistemaPedidos extends Subject {
    private $repo;

    public function __construct() {
        $this->repo = new RepositorioPedidos();
    }

    public function criarPedido($id, $descricao) {
        if ($this->repo->buscarPorId($id)) {
            echo "\033[31m Pedido com ID {$id} já existe!\033[0m\n";
            return;
        }
        $pedido = ['id' => $id, 'descricao' => $descricao, 'status' => 'criado'];
        $this->repo->salvar($pedido);
        echo "\033[32m Pedido {$id} criado com sucesso!\033[0m\n";
        $this->notify($pedido);
    }

    public function listarPedidos() {
        $pedidos = $this->repo->listar();
        if (empty($pedidos)) {
            echo "\n Nenhum pedido encontrado.\n";
            return;
        }
        echo "\n LISTA DE PEDIDOS:\n";
        echo "--------------------------\n";
        foreach ($pedidos as $pedido) {
            echo "ID: {$pedido['id']}\n";
            echo "Descrição: {$pedido['descricao']}\n";
            echo "Status: {$pedido['status']}\n";
            echo "--------------------------\n";
        }
    }

    public function atualizarStatus($id, $status) {
        $this->repo->atualizarStatus($id, $status);
        echo "\033[36m Status do pedido {$id} atualizado para {$status}\033[0m\n";
    }

    public function removerPedido($id) {
        $this->repo->remover($id);
        echo "\033[31m Pedido {$id} removido!\033[0m\n";
    }

    public function buscarPorId($id) {
        return $this->repo->buscarPorId($id);
    }
}


// MENU INTERATIVO

$sistema = new SistemaPedidos();
$sistema->subscribe(new EmailObserver());
$sistema->subscribe(new EstoqueObserver());
$sistema->subscribe(new LogObserver());

while (true) {
    echo "\n=== SISTEMA DE PEDIDOS ONLINE ===\n";
    echo "1 - Criar pedido\n";
    echo "2 - Listar pedidos\n";
    echo "3 - Atualizar status\n";
    echo "4 - Remover pedido\n";
    echo "5 - Buscar pedido por ID\n";
    echo "6 - Sair\n";
    echo "Escolha uma opção: ";

    $opcao = trim(fgets(STDIN));

    switch ($opcao) {
        case 1:
            echo "Digite o ID do pedido: ";
            $id = trim(fgets(STDIN));
            echo "Digite a descrição do pedido: ";
            $descricao = trim(fgets(STDIN));
            $sistema->criarPedido($id, $descricao);
            break;
        case 2:
            $sistema->listarPedidos();
            break;
        case 3:
            echo "Digite o ID do pedido: ";
            $id = trim(fgets(STDIN));
            echo "Digite o novo status: ";
            $status = trim(fgets(STDIN));
            $sistema->atualizarStatus($id, $status);
            break;
        case 4:
            echo "Digite o ID do pedido: ";
            $id = trim(fgets(STDIN));
            $sistema->removerPedido($id);
            break;
        case 5:
            echo "Digite o ID do pedido: ";
            $id = trim(fgets(STDIN));
            $pedido = $sistema->buscarPorId($id);
            if ($pedido) {
                echo "ID: {$pedido['id']} - {$pedido['descricao']} ({$pedido['status']})\n";
            } else {
                echo " Pedido não encontrado!\n";
            }
            break;
        case 6:
            echo "\n Encerrando sistema...\n";
            exit;
        default:
            echo " Opção inválida!\n";
    }
}
