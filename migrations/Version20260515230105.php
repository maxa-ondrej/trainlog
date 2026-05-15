<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260515230105 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE exercise (id INT AUTO_INCREMENT NOT NULL, owner_id INT NOT NULL, name VARCHAR(120) NOT NULL, description LONGTEXT DEFAULT NULL, is_public TINYINT(1) NOT NULL, INDEX IDX_AEDAD51C7E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE exercise_muscle_group (exercise_id INT NOT NULL, muscle_group_id INT NOT NULL, INDEX IDX_D8A5BCA7E934951A (exercise_id), INDEX IDX_D8A5BCA744004D0 (muscle_group_id), PRIMARY KEY(exercise_id, muscle_group_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE muscle_group (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(80) NOT NULL, UNIQUE INDEX muscle_group_name_unique (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, name VARCHAR(120) NOT NULL, role VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX user_email_unique (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE workout (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, performed_at DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', name VARCHAR(120) NOT NULL, note LONGTEXT DEFAULT NULL, duration_minutes INT DEFAULT NULL, is_template TINYINT(1) NOT NULL, INDEX IDX_649FFB72A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE workout_set (id INT AUTO_INCREMENT NOT NULL, workout_id INT NOT NULL, exercise_id INT NOT NULL, position INT NOT NULL, reps INT NOT NULL, weight_kg NUMERIC(6, 2) NOT NULL, rpe NUMERIC(3, 1) DEFAULT NULL, INDEX IDX_6FDEFB94A6CCCFC9 (workout_id), INDEX IDX_6FDEFB94E934951A (exercise_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE exercise ADD CONSTRAINT FK_AEDAD51C7E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE exercise_muscle_group ADD CONSTRAINT FK_D8A5BCA7E934951A FOREIGN KEY (exercise_id) REFERENCES exercise (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE exercise_muscle_group ADD CONSTRAINT FK_D8A5BCA744004D0 FOREIGN KEY (muscle_group_id) REFERENCES muscle_group (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE workout ADD CONSTRAINT FK_649FFB72A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE workout_set ADD CONSTRAINT FK_6FDEFB94A6CCCFC9 FOREIGN KEY (workout_id) REFERENCES workout (id)');
        $this->addSql('ALTER TABLE workout_set ADD CONSTRAINT FK_6FDEFB94E934951A FOREIGN KEY (exercise_id) REFERENCES exercise (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE exercise DROP FOREIGN KEY FK_AEDAD51C7E3C61F9');
        $this->addSql('ALTER TABLE exercise_muscle_group DROP FOREIGN KEY FK_D8A5BCA7E934951A');
        $this->addSql('ALTER TABLE exercise_muscle_group DROP FOREIGN KEY FK_D8A5BCA744004D0');
        $this->addSql('ALTER TABLE workout DROP FOREIGN KEY FK_649FFB72A76ED395');
        $this->addSql('ALTER TABLE workout_set DROP FOREIGN KEY FK_6FDEFB94A6CCCFC9');
        $this->addSql('ALTER TABLE workout_set DROP FOREIGN KEY FK_6FDEFB94E934951A');
        $this->addSql('DROP TABLE exercise');
        $this->addSql('DROP TABLE exercise_muscle_group');
        $this->addSql('DROP TABLE muscle_group');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE workout');
        $this->addSql('DROP TABLE workout_set');
    }
}
