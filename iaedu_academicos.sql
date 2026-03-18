-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 18-Mar-2026 às 13:32
-- Versão do servidor: 10.4.32-MariaDB
-- versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `iaedu_academicos`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `courses`
--

CREATE TABLE `courses` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(30) NOT NULL,
  `name` varchar(160) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `courses`
--

INSERT INTO `courses` (`id`, `code`, `name`, `active`, `created_at`) VALUES
(1, 'LEI', 'Licenciatura em Engenharia Informática', 1, '2026-03-13 14:36:45'),
(2, 'LGE', 'Licenciatura em Gestão', 1, '2026-03-13 14:36:45');

-- --------------------------------------------------------

--
-- Estrutura da tabela `enrollment_requests`
--

CREATE TABLE `enrollment_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `course2_id` int(10) UNSIGNED DEFAULT NULL,
  `course3_id` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `decided_by` int(10) UNSIGNED DEFAULT NULL,
  `decided_at` datetime DEFAULT NULL,
  `decision_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `enrollment_requests`
--

INSERT INTO `enrollment_requests` (`id`, `user_id`, `course_id`, `course2_id`, `course3_id`, `status`, `created_at`, `decided_by`, `decided_at`, `decision_notes`) VALUES
(1, 1, 1, NULL, NULL, 'approved', '2026-03-13 22:09:35', 2, '2026-03-13 22:10:53', ''),
(2, 4, 1, NULL, NULL, 'approved', '2026-03-18 10:14:36', 2, '2026-03-18 10:21:43', '');

-- --------------------------------------------------------

--
-- Estrutura da tabela `grade_sheets`
--

CREATE TABLE `grade_sheets` (
  `id` int(10) UNSIGNED NOT NULL,
  `uc_id` int(10) UNSIGNED NOT NULL,
  `academic_year` varchar(9) NOT NULL,
  `season` enum('normal','recurso','especial') NOT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `grade_sheets`
--

INSERT INTO `grade_sheets` (`id`, `uc_id`, `academic_year`, `season`, `created_by`, `created_at`) VALUES
(1, 2, '2026/2027', 'normal', 2, '2026-03-13 22:11:02');

-- --------------------------------------------------------

--
-- Estrutura da tabela `grade_sheet_rows`
--

CREATE TABLE `grade_sheet_rows` (
  `id` int(10) UNSIGNED NOT NULL,
  `grade_sheet_id` int(10) UNSIGNED NOT NULL,
  `student_user_id` int(10) UNSIGNED NOT NULL,
  `final_grade` decimal(4,1) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `grade_sheet_rows`
--

INSERT INTO `grade_sheet_rows` (`id`, `grade_sheet_id`, `student_user_id`, `final_grade`, `updated_at`) VALUES
(1, 1, 1, 12.0, '2026-03-13 22:11:25');

-- --------------------------------------------------------

--
-- Estrutura da tabela `roles`
--

CREATE TABLE `roles` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `name` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `roles`
--

INSERT INTO `roles` (`id`, `name`) VALUES
(1, 'aluno'),
(2, 'funcionario'),
(3, 'gestor');

-- --------------------------------------------------------

--
-- Estrutura da tabela `student_course_enrollments`
--

CREATE TABLE `student_course_enrollments` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `academic_year` varchar(9) NOT NULL,
  `status` enum('active','cancelled') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `student_course_enrollments`
--

INSERT INTO `student_course_enrollments` (`id`, `user_id`, `course_id`, `academic_year`, `status`, `created_at`) VALUES
(1, 1, 1, '2026/2027', 'active', '2026-03-13 22:10:53'),
(2, 4, 1, '2026/2027', 'active', '2026-03-18 10:21:43');

-- --------------------------------------------------------

--
-- Estrutura da tabela `student_profiles`
--

CREATE TABLE `student_profiles` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(160) NOT NULL,
  `birth_date` date DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `desired_course_id` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('draft','submitted','approved','rejected') NOT NULL DEFAULT 'draft',
  `submitted_at` datetime DEFAULT NULL,
  `decided_by` int(10) UNSIGNED DEFAULT NULL,
  `decided_at` datetime DEFAULT NULL,
  `decision_notes` text DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `student_profiles`
--

INSERT INTO `student_profiles` (`id`, `user_id`, `full_name`, `birth_date`, `phone`, `address`, `photo_path`, `desired_course_id`, `status`, `submitted_at`, `decided_by`, `decided_at`, `decision_notes`, `updated_at`) VALUES
(1, 1, 'Dinis', '2007-12-27', '910832179', 'aaaaa', '/aula/tp1/public/uploads/photos/u1_8c0f3d0fc381e187.png', 1, 'approved', '2026-03-13 16:10:42', 3, '2026-03-18 09:47:45', '', '2026-03-18 09:47:45'),
(2, 4, 'Lionel', '2007-12-26', '910832179', 'aaaaa', '/aula/tp1/public/uploads/photos/u4_ba79b7160c21acd4.png', 1, 'approved', '2026-03-18 09:46:15', 3, '2026-03-18 09:47:40', '', '2026-03-18 09:47:40');

-- --------------------------------------------------------

--
-- Estrutura da tabela `student_uc_enrollments`
--

CREATE TABLE `student_uc_enrollments` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `uc_id` int(10) UNSIGNED NOT NULL,
  `academic_year` varchar(9) NOT NULL,
  `status` enum('enrolled','cancelled') NOT NULL DEFAULT 'enrolled',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `student_uc_enrollments`
--

INSERT INTO `student_uc_enrollments` (`id`, `user_id`, `uc_id`, `academic_year`, `status`, `created_at`) VALUES
(1, 1, 3, '2026/2027', 'enrolled', '2026-03-13 22:16:53');

-- --------------------------------------------------------

--
-- Estrutura da tabela `study_plans`
--

CREATE TABLE `study_plans` (
  `id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `uc_id` int(10) UNSIGNED NOT NULL,
  `year_no` tinyint(3) UNSIGNED NOT NULL,
  `semester_no` tinyint(3) UNSIGNED NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `study_plans`
--

INSERT INTO `study_plans` (`id`, `course_id`, `uc_id`, `year_no`, `semester_no`, `active`) VALUES
(1, 1, 3, 1, 1, 1),
(2, 1, 1, 1, 1, 1),
(4, 1, 2, 1, 2, 1);

-- --------------------------------------------------------

--
-- Estrutura da tabela `ucs`
--

CREATE TABLE `ucs` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(30) NOT NULL,
  `name` varchar(160) NOT NULL,
  `ects` decimal(4,1) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `ucs`
--

INSERT INTO `ucs` (`id`, `code`, `name`, `ects`, `active`) VALUES
(1, 'PROG1', 'Programação I', 6.0, 1),
(2, 'BD1', 'Bases de Dados I', 6.0, 1),
(3, 'MAT1', 'Matemática I', 6.0, 1);

-- --------------------------------------------------------

--
-- Estrutura da tabela `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `role_id` tinyint(3) UNSIGNED NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `name` varchar(120) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `users`
--

INSERT INTO `users` (`id`, `role_id`, `email`, `password_hash`, `name`, `active`, `created_at`) VALUES
(1, 1, 'aluno@demo.pt', '$2y$10$yleydqnA2K.Mv8mtyadUj.V90lLHjFnX5XBQIQWAFwgvyUMf37gnm', 'Aluno Demo', 1, '2026-03-13 15:00:52'),
(2, 2, 'func@demo.pt', '$2y$10$7nJQaASJlB3RzTTTGJwyTO9jyfi1gMARLlZivoC6Q3qknIzw7pm6W', 'Funcionário Demo', 1, '2026-03-13 15:00:52'),
(3, 3, 'gestor@demo.pt', '$2y$10$1afgFsAUEcs3bJBthd7FsOYC5k5.BVDq7lVv80q9XDNRi2E2IcJ/C', 'Gestor Demo', 1, '2026-03-13 15:00:52'),
(4, 1, 'lionel@gmail.com', '$2y$10$P5mlYRVXP65uINa/tcc8wur8L5I4wmv.NyT7UIJz.w9RdZn45c8Na', 'Lionel', 1, '2026-03-18 09:45:22');

-- --------------------------------------------------------

--
-- Estrutura stand-in para vista `v_pending_enrollment_requests`
-- (Veja abaixo para a view atual)
--
CREATE TABLE `v_pending_enrollment_requests` (
`id` int(10) unsigned
,`user_id` int(10) unsigned
,`email` varchar(190)
,`user_name` varchar(120)
,`course_id` int(10) unsigned
,`course_name` varchar(160)
,`status` enum('pending','approved','rejected')
,`created_at` datetime
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para vista `v_submitted_profiles`
-- (Veja abaixo para a view atual)
--
CREATE TABLE `v_submitted_profiles` (
`id` int(10) unsigned
,`user_id` int(10) unsigned
,`email` varchar(190)
,`user_name` varchar(120)
,`full_name` varchar(160)
,`desired_course_id` int(10) unsigned
,`desired_course_name` varchar(160)
,`status` enum('draft','submitted','approved','rejected')
,`submitted_at` datetime
);

-- --------------------------------------------------------

--
-- Estrutura para vista `v_pending_enrollment_requests`
--
DROP TABLE IF EXISTS `v_pending_enrollment_requests`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_pending_enrollment_requests`  AS SELECT `er`.`id` AS `id`, `er`.`user_id` AS `user_id`, `u`.`email` AS `email`, `u`.`name` AS `user_name`, `er`.`course_id` AS `course_id`, `c`.`name` AS `course_name`, `er`.`status` AS `status`, `er`.`created_at` AS `created_at` FROM ((`enrollment_requests` `er` join `users` `u` on(`u`.`id` = `er`.`user_id`)) join `courses` `c` on(`c`.`id` = `er`.`course_id`)) WHERE `er`.`status` = 'pending' ;

-- --------------------------------------------------------

--
-- Estrutura para vista `v_submitted_profiles`
--
DROP TABLE IF EXISTS `v_submitted_profiles`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_submitted_profiles`  AS SELECT `sp`.`id` AS `id`, `sp`.`user_id` AS `user_id`, `u`.`email` AS `email`, `u`.`name` AS `user_name`, `sp`.`full_name` AS `full_name`, `sp`.`desired_course_id` AS `desired_course_id`, `c`.`name` AS `desired_course_name`, `sp`.`status` AS `status`, `sp`.`submitted_at` AS `submitted_at` FROM ((`student_profiles` `sp` join `users` `u` on(`u`.`id` = `sp`.`user_id`)) left join `courses` `c` on(`c`.`id` = `sp`.`desired_course_id`)) WHERE `sp`.`status` = 'submitted' ;

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_courses_code` (`code`),
  ADD KEY `idx_courses_active` (`active`);

--
-- Índices para tabela `enrollment_requests`
--
ALTER TABLE `enrollment_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_er_status` (`status`),
  ADD KEY `fk_er_user` (`user_id`),
  ADD KEY `fk_er_course` (`course_id`),
  ADD KEY `fk_er_decided_by` (`decided_by`),
  ADD KEY `fk_er_course2_v2` (`course2_id`),
  ADD KEY `fk_er_course3_v2` (`course3_id`);

--
-- Índices para tabela `grade_sheets`
--
ALTER TABLE `grade_sheets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_gs_one` (`uc_id`,`academic_year`,`season`),
  ADD KEY `fk_gs_created_by` (`created_by`);

--
-- Índices para tabela `grade_sheet_rows`
--
ALTER TABLE `grade_sheet_rows`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_gsr_one_student_per_sheet` (`grade_sheet_id`,`student_user_id`),
  ADD KEY `fk_gsr_student` (`student_user_id`);

--
-- Índices para tabela `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_roles_name` (`name`);

--
-- Índices para tabela `student_course_enrollments`
--
ALTER TABLE `student_course_enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sce_one_per_year` (`user_id`,`course_id`,`academic_year`),
  ADD KEY `fk_sce_course` (`course_id`);

--
-- Índices para tabela `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_student_profiles_user` (`user_id`),
  ADD KEY `idx_profile_status` (`status`),
  ADD KEY `fk_profile_desired_course` (`desired_course_id`),
  ADD KEY `fk_profile_decided_by` (`decided_by`);

--
-- Índices para tabela `student_uc_enrollments`
--
ALTER TABLE `student_uc_enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sue_one_per_year` (`user_id`,`uc_id`,`academic_year`),
  ADD KEY `fk_sue_uc` (`uc_id`);

--
-- Índices para tabela `study_plans`
--
ALTER TABLE `study_plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sp_no_dupes` (`course_id`,`uc_id`,`year_no`,`semester_no`),
  ADD KEY `idx_sp_course` (`course_id`),
  ADD KEY `idx_sp_uc` (`uc_id`);

--
-- Índices para tabela `ucs`
--
ALTER TABLE `ucs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ucs_code` (`code`),
  ADD KEY `idx_ucs_active` (`active`);

--
-- Índices para tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD KEY `idx_users_role` (`role_id`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `enrollment_requests`
--
ALTER TABLE `enrollment_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `grade_sheets`
--
ALTER TABLE `grade_sheets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `grade_sheet_rows`
--
ALTER TABLE `grade_sheet_rows`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `roles`
--
ALTER TABLE `roles`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `student_course_enrollments`
--
ALTER TABLE `student_course_enrollments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `student_profiles`
--
ALTER TABLE `student_profiles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `student_uc_enrollments`
--
ALTER TABLE `student_uc_enrollments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `study_plans`
--
ALTER TABLE `study_plans`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `ucs`
--
ALTER TABLE `ucs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `enrollment_requests`
--
ALTER TABLE `enrollment_requests`
  ADD CONSTRAINT `fk_er_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_er_course2_v2` FOREIGN KEY (`course2_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_er_course3` FOREIGN KEY (`course3_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_er_course3_v2` FOREIGN KEY (`course3_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_er_decided_by` FOREIGN KEY (`decided_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_er_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limitadores para a tabela `grade_sheets`
--
ALTER TABLE `grade_sheets`
  ADD CONSTRAINT `fk_gs_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_gs_uc` FOREIGN KEY (`uc_id`) REFERENCES `ucs` (`id`) ON UPDATE CASCADE;

--
-- Limitadores para a tabela `grade_sheet_rows`
--
ALTER TABLE `grade_sheet_rows`
  ADD CONSTRAINT `fk_gsr_sheet` FOREIGN KEY (`grade_sheet_id`) REFERENCES `grade_sheets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_gsr_student` FOREIGN KEY (`student_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Limitadores para a tabela `student_course_enrollments`
--
ALTER TABLE `student_course_enrollments`
  ADD CONSTRAINT `fk_sce_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sce_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limitadores para a tabela `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD CONSTRAINT `fk_profile_decided_by` FOREIGN KEY (`decided_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_profile_desired_course` FOREIGN KEY (`desired_course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_profile_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limitadores para a tabela `student_uc_enrollments`
--
ALTER TABLE `student_uc_enrollments`
  ADD CONSTRAINT `fk_sue_uc` FOREIGN KEY (`uc_id`) REFERENCES `ucs` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sue_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Limitadores para a tabela `study_plans`
--
ALTER TABLE `study_plans`
  ADD CONSTRAINT `fk_sp_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sp_uc` FOREIGN KEY (`uc_id`) REFERENCES `ucs` (`id`) ON UPDATE CASCADE;

--
-- Limitadores para a tabela `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
