<?php

declare(strict_types=1);

use App\Core\Database;

/*
 * Legacy: jobs, jobranks, courses, coursesdone.
 */
return new class {
    private const OPTIONS = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

    public function up(Database $db): void
    {
        $db->query('CREATE TABLE `jobs` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            first_rank_id INT UNSIGNED NULL,
            description VARCHAR(255) NOT NULL DEFAULT \'\',
            owner VARCHAR(255) NOT NULL DEFAULT \'\'
        ) ' . self::OPTIONS);

        // *_gain: stats earned per working day; *_required: needed for promotion to this rank.
        $db->query('CREATE TABLE `job_ranks` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            job_id INT UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            pay BIGINT NOT NULL DEFAULT 0,
            iq_gain INT NOT NULL DEFAULT 0,
            labour_gain INT NOT NULL DEFAULT 0,
            strength_gain INT NOT NULL DEFAULT 0,
            iq_required INT NOT NULL DEFAULT 0,
            labour_required INT NOT NULL DEFAULT 0,
            strength_required INT NOT NULL DEFAULT 0,
            CONSTRAINT job_ranks_job_fk FOREIGN KEY (job_id) REFERENCES jobs (id) ON DELETE CASCADE
        ) ' . self::OPTIONS);

        $db->query('ALTER TABLE `jobs`
            ADD CONSTRAINT jobs_first_rank_fk FOREIGN KEY (first_rank_id) REFERENCES job_ranks (id) ON DELETE SET NULL');

        $db->query('CREATE TABLE `courses` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT NULL,
            cost BIGINT NOT NULL DEFAULT 0,
            energy INT NOT NULL DEFAULT 0,
            days INT NOT NULL DEFAULT 1,
            strength INT NOT NULL DEFAULT 0,
            guard INT NOT NULL DEFAULT 0,
            labour INT NOT NULL DEFAULT 0,
            agility INT NOT NULL DEFAULT 0,
            iq INT NOT NULL DEFAULT 0
        ) ' . self::OPTIONS);

        $db->query('CREATE TABLE `course_completions` (
            user_id INT UNSIGNED NOT NULL,
            course_id INT UNSIGNED NOT NULL,
            completed_at DATETIME NOT NULL,
            PRIMARY KEY (user_id, course_id),
            CONSTRAINT course_completions_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
            CONSTRAINT course_completions_course_fk FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE CASCADE
        ) ' . self::OPTIONS);
    }

    public function down(Database $db): void
    {
        $db->query('DROP TABLE IF EXISTS `course_completions`');
        $db->query('DROP TABLE IF EXISTS `courses`');
        $db->query('ALTER TABLE `jobs` DROP FOREIGN KEY jobs_first_rank_fk');
        $db->query('DROP TABLE IF EXISTS `job_ranks`');
        $db->query('DROP TABLE IF EXISTS `jobs`');
    }
};
