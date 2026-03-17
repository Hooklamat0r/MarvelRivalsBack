<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260315000001 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE personajes (
            id VARCHAR(255) NOT NULL,
            name VARCHAR(255) NOT NULL,
            image_url LONGTEXT DEFAULT NULL,
            role VARCHAR(100) DEFAULT NULL,
            difficulty VARCHAR(50) DEFAULT NULL,
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE usuario (
            id INT AUTO_INCREMENT NOT NULL,
            nombre VARCHAR(255) NOT NULL,
            apellido VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            contrasena VARCHAR(255) NOT NULL,
            rol VARCHAR(255) NOT NULL,
            active TINYINT NOT NULL,
            premium_until DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE usuarios_personajes (
            id INT AUTO_INCREMENT NOT NULL,
            añadido_en DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            usuario_id INT NOT NULL,
            personaje_id VARCHAR(255) NOT NULL,
            INDEX IDX_BE65F486DB38439E (usuario_id),
            INDEX IDX_BE65F486121EFAFB (personaje_id),
            UNIQUE INDEX UNIQUE_usuario_personaje (usuario_id, personaje_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE teamups (
            id INT AUTO_INCREMENT NOT NULL,
            nombre VARCHAR(255) NOT NULL,
            personaje1_id VARCHAR(255) NOT NULL,
            personaje2_id VARCHAR(255) NOT NULL,
            descripcion TEXT NOT NULL,
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id),
            INDEX IDX_personaje1 (personaje1_id),
            INDEX IDX_personaje2 (personaje2_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE partidas (
            id INT AUTO_INCREMENT NOT NULL,
            retador_id INT NOT NULL,
            retado_id INT NOT NULL,
            ganador_id INT DEFAULT NULL,
            estado VARCHAR(255) NOT NULL,
            tipo_reto VARCHAR(20) NOT NULL DEFAULT \'amigo\',
            equipo_retador JSON DEFAULT NULL,
            equipo_retado JSON DEFAULT NULL,
            puntuacion_retador INT DEFAULT NULL,
            puntuacion_retado INT DEFAULT NULL,
            detalle_retador JSON DEFAULT NULL,
            detalle_retado JSON DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            responded_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            resolved_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_PARTIDA_RETADOR (retador_id),
            INDEX IDX_PARTIDA_RETADO (retado_id),
            INDEX IDX_PARTIDA_GANADOR (ganador_id),
            INDEX IDX_PARTIDA_ESTADO (estado),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE amistades (
            id INT AUTO_INCREMENT NOT NULL,
            solicitante_id INT NOT NULL,
            receptor_id INT NOT NULL,
            estado VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            responded_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_AMISTAD_SOLICITANTE (solicitante_id),
            INDEX IDX_AMISTAD_RECEPTOR (receptor_id),
            INDEX IDX_AMISTAD_ESTADO (estado),
            UNIQUE INDEX UNIQ_AMISTAD_DIRECCION (solicitante_id, receptor_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE pagos (
            id INT AUTO_INCREMENT NOT NULL,
            usuario_id INT NOT NULL,
            meses INT NOT NULL,
            precio INT NOT NULL,
            premium_until DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_PAGO_USUARIO (usuario_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE usuarios_personajes ADD CONSTRAINT FK_BE65F486DB38439E FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE usuarios_personajes ADD CONSTRAINT FK_BE65F486121EFAFB FOREIGN KEY (personaje_id) REFERENCES personajes(id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE teamups ADD CONSTRAINT FK_teamup_personaje1 FOREIGN KEY (personaje1_id) REFERENCES personajes(id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE teamups ADD CONSTRAINT FK_teamup_personaje2 FOREIGN KEY (personaje2_id) REFERENCES personajes(id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE partidas ADD CONSTRAINT FK_PARTIDA_RETADOR FOREIGN KEY (retador_id) REFERENCES usuario (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE partidas ADD CONSTRAINT FK_PARTIDA_RETADO FOREIGN KEY (retado_id) REFERENCES usuario (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE partidas ADD CONSTRAINT FK_PARTIDA_GANADOR FOREIGN KEY (ganador_id) REFERENCES usuario (id) ON DELETE SET NULL');

        $this->addSql('ALTER TABLE amistades ADD CONSTRAINT FK_AMISTAD_SOLICITANTE FOREIGN KEY (solicitante_id) REFERENCES usuario (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE amistades ADD CONSTRAINT FK_AMISTAD_RECEPTOR FOREIGN KEY (receptor_id) REFERENCES usuario (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE pagos ADD CONSTRAINT FK_PAGO_USUARIO FOREIGN KEY (usuario_id) REFERENCES usuario (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE pagos');
        $this->addSql('DROP TABLE amistades');
        $this->addSql('DROP TABLE partidas');
        $this->addSql('DROP TABLE teamups');
        $this->addSql('DROP TABLE usuarios_personajes');
        $this->addSql('DROP TABLE usuario');
        $this->addSql('DROP TABLE personajes');
    }
}
