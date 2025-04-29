USE tarefas;

CREATE TABLE compras (
    id_compra INT PRIMARY KEY AUTO_INCREMENT,
    produto VARCHAR(100) NOT NULL,
    quantidade INT,
    concluido BOOLEAN DEFAULT FALSE
);
DELETE FROM compromissos WHERE concluido = TRUE;
DELETE FROM compras WHERE concluido = TRUE;

CREATE TABLE compromissos (
    id_compromissos INT PRIMARY KEY AUTO_INCREMENT,
    descricao VARCHAR(250) NOT NULL,
    data DATE,
    hora TIME,
    concluido BOOLEAN DEFAULT FALSE
);