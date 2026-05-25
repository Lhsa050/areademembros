<?php
/**
 * Schema do Banco de Dados
 */

return [
    // Tabela de Administradores
    'admins' => "
        CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            uuid CHAR(36) NOT NULL UNIQUE,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(191) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('super_admin', 'admin') NOT NULL DEFAULT 'admin',
            status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
            last_login_at TIMESTAMP NULL,
            last_login_ip VARCHAR(45) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    // Tabela de Funis
    'funnels' => "
        CREATE TABLE IF NOT EXISTS funnels (
            id INT AUTO_INCREMENT PRIMARY KEY,
            uuid CHAR(36) NOT NULL UNIQUE,
            name VARCHAR(200) NOT NULL,
            slug VARCHAR(200) NULL UNIQUE,
            description TEXT NULL,
            site_name VARCHAR(200) NULL,
            theme ENUM('elegante-escuro', 'elegante-claro', 'moderno-azul', 'moderno-verde', 'premium-dourado', 'minimalista') NOT NULL DEFAULT 'minimalista',
            auto_organize TINYINT NOT NULL DEFAULT 0,
            language VARCHAR(10) NOT NULL DEFAULT 'pt-BR',
            webhook_token VARCHAR(64) NULL UNIQUE,
            custom_translations JSON NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_name (name),
            INDEX idx_webhook_token (webhook_token),
            INDEX idx_slug (slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    // Tabela de Configurações
    'settings' => "
        CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_key (setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    // Configuracoes especificas por funil
    'funnel_settings' => "
        CREATE TABLE IF NOT EXISTS funnel_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            funnel_id INT NOT NULL,
            setting_key VARCHAR(100) NOT NULL,
            setting_value TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_funnel_setting (funnel_id, setting_key),
            INDEX idx_funnel (funnel_id),
            INDEX idx_key (setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    // Tabela de Produtos
    'products' => "
        CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            funnel_id INT NULL,
            type ENUM('video', 'pdf') NOT NULL,
            title VARCHAR(200) NOT NULL,
            description TEXT NOT NULL,
            image VARCHAR(255) NULL,
            file VARCHAR(255) NULL,
            checkout_url VARCHAR(500) NULL,
            webhook_token VARCHAR(64) NULL UNIQUE,
            external_product_id VARCHAR(191) NULL,
            sort_order INT NOT NULL DEFAULT 0,
            release_days INT NULL DEFAULT NULL,
            is_public TINYINT NOT NULL DEFAULT 0,
            price DECIMAL(12,2) NULL,
            fiscal_kind VARCHAR(20) NULL,
            fiscal_service_code VARCHAR(30) NULL,
            fiscal_service_description TEXT NULL,
            fiscal_nbs_code VARCHAR(30) NULL,
            fiscal_iss_rate DECIMAL(5,2) NULL,
            fiscal_tax_group_id INT NULL,
            fiscal_document_model VARCHAR(20) NULL,
            fiscal_issue_policy VARCHAR(30) NULL,
            fiscal_warranty_days INT NULL,
            fiscal_lc116_code VARCHAR(30) NULL,
            fiscal_municipal_service_code VARCHAR(50) NULL,
            fiscal_cnae_code VARCHAR(20) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_funnel (funnel_id),
            INDEX idx_type (type),
            INDEX idx_sort (sort_order),
            INDEX idx_webhook_token (webhook_token)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    // Tabela de vínculo entre Funis e Produtos globais
    'funnel_products' => "
        CREATE TABLE IF NOT EXISTS funnel_products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            funnel_id INT NOT NULL,
            product_id INT NOT NULL,
            checkout_url VARCHAR(500) NULL,
            webhook_token VARCHAR(64) NULL UNIQUE,
            external_product_id VARCHAR(191) NULL,
            funnel_role ENUM('principal', 'bonus', 'orderbump') NULL DEFAULT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            release_days INT NULL DEFAULT NULL,
            is_public TINYINT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_funnel_product (funnel_id, product_id),
            INDEX idx_funnel (funnel_id),
            INDEX idx_product (product_id),
            INDEX idx_sort (sort_order),
            INDEX idx_webhook_token (webhook_token),
            INDEX idx_external_product (funnel_id, external_product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    // Tabela de Arquivos do Produto
    'product_files' => "
        CREATE TABLE IF NOT EXISTS product_files (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            file VARCHAR(500) NOT NULL,
            file_type ENUM('upload','link') NOT NULL DEFAULT 'upload',
            open_in_new_tab TINYINT NOT NULL DEFAULT 0,
            sort_order INT NOT NULL DEFAULT 0,
            release_days INT NULL DEFAULT NULL,
            INDEX idx_product (product_id),
            INDEX idx_sort (sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    // Tabela de Módulos
    'modules' => "
        CREATE TABLE IF NOT EXISTS modules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            release_days INT NULL DEFAULT NULL,
            INDEX idx_product (product_id),
            INDEX idx_sort (sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    // Tabela de Aulas
    'lessons' => "
        CREATE TABLE IF NOT EXISTS lessons (
            id INT AUTO_INCREMENT PRIMARY KEY,
            module_id INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            description TEXT NULL,
            youtube_id VARCHAR(100) NULL,
            file VARCHAR(255) NULL,
            sort_order INT NOT NULL DEFAULT 0,
            release_days INT NULL DEFAULT NULL,
            INDEX idx_module (module_id),
            INDEX idx_sort (sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    // Tabela de Arquivos de Aulas (múltiplos arquivos por aula)
    'lesson_files' => "
        CREATE TABLE IF NOT EXISTS lesson_files (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lesson_id INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            file VARCHAR(500) NOT NULL,
            file_type ENUM('upload','link') NOT NULL DEFAULT 'upload',
            sort_order INT NOT NULL DEFAULT 0,
            release_days INT NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_lesson (lesson_id),
            INDEX idx_sort (sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    // Tabela de Membros (Usuários/Compradores) — escopado por funil
    'members' => "
        CREATE TABLE IF NOT EXISTS members (
            id INT AUTO_INCREMENT PRIMARY KEY,
            uuid CHAR(36) NOT NULL UNIQUE,
            funnel_id INT NOT NULL,
            name VARCHAR(200) NOT NULL,
            email VARCHAR(191) NOT NULL,
            cpf VARCHAR(20) NULL,
            phone VARCHAR(30) NULL,
            password VARCHAR(255) NULL,
            status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
            last_login_at TIMESTAMP NULL,
            last_login_ip VARCHAR(45) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_member_funnel (funnel_id, email),
            INDEX idx_funnel (funnel_id),
            INDEX idx_email (email),
            INDEX idx_cpf (cpf),
            INDEX idx_phone (phone),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    // Tabela de Produtos do Membro
    'member_products' => "
        CREATE TABLE IF NOT EXISTS member_products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            member_id INT NOT NULL,
            product_id INT NOT NULL,
            granted_by ENUM('webhook', 'admin') NOT NULL DEFAULT 'webhook',
            granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            revoked_at TIMESTAMP NULL,
            UNIQUE KEY unique_member_product (member_id, product_id),
            INDEX idx_member (member_id),
            INDEX idx_product (product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    // Tabela de compras/acessos por pedido recebido via webhook
    'member_product_orders' => "
        CREATE TABLE IF NOT EXISTS member_product_orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            funnel_id INT NOT NULL,
            member_id INT NULL,
            customer_email VARCHAR(191) NOT NULL,
            product_id INT NOT NULL,
            course_id INT NULL,
            order_id VARCHAR(191) NOT NULL,
            order_number VARCHAR(191) NULL,
            source_platform VARCHAR(50) NOT NULL DEFAULT 'cartpanda',
            source_event VARCHAR(100) NULL,
            external_product_id VARCHAR(191) NULL,
            payment_method VARCHAR(80) NULL,
            payment_status ENUM('pending','paid','cancelled','refunded','chargeback') NOT NULL DEFAULT 'pending',
            access_status ENUM('none','active','revoked') NOT NULL DEFAULT 'none',
            paid_at DATETIME NULL,
            refunded_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_order_product (source_platform, order_id, product_id),
            INDEX idx_member_product (member_id, product_id),
            INDEX idx_email_product (customer_email, product_id),
            INDEX idx_order (order_id),
            INDEX idx_status (payment_status, access_status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    // Tabela de Logs de Webhook
    'webhook_logs' => "
        CREATE TABLE IF NOT EXISTS webhook_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NULL,
            event_type VARCHAR(100) NULL,
            payload JSON NULL,
            ip VARCHAR(45) NULL,
            processed TINYINT NOT NULL DEFAULT 0,
            error TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_product (product_id),
            INDEX idx_event (event_type),
            INDEX idx_processed (processed),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    // Tabela de Gerações (histórico)
    'offers' => "
        CREATE TABLE IF NOT EXISTS offers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            funnel_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NULL,
            image VARCHAR(500) NULL,
            checkout_url VARCHAR(500) NULL,
            webhook_token VARCHAR(100) NOT NULL,
            is_active TINYINT NOT NULL DEFAULT 1,
            show_as_popup TINYINT NOT NULL DEFAULT 0,
            price DECIMAL(12,2) NULL,
            fiscal_kind VARCHAR(20) NULL,
            fiscal_service_code VARCHAR(30) NULL,
            fiscal_service_description TEXT NULL,
            fiscal_nbs_code VARCHAR(30) NULL,
            fiscal_iss_rate DECIMAL(5,2) NULL,
            fiscal_tax_group_id INT NULL,
            fiscal_document_model VARCHAR(20) NULL,
            fiscal_issue_policy VARCHAR(30) NULL,
            fiscal_warranty_days INT NULL,
            fiscal_lc116_code VARCHAR(30) NULL,
            fiscal_municipal_service_code VARCHAR(50) NULL,
            fiscal_cnae_code VARCHAR(20) NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_funnel (funnel_id),
            INDEX idx_webhook (webhook_token),
            INDEX idx_active (funnel_id, is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    'offer_products' => "
        CREATE TABLE IF NOT EXISTS offer_products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            offer_id INT NOT NULL,
            product_id INT NOT NULL,
            INDEX idx_offer (offer_id),
            INDEX idx_product (product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    'fiscal_sales' => "
        CREATE TABLE IF NOT EXISTS fiscal_sales (
            id INT AUTO_INCREMENT PRIMARY KEY,
            uuid CHAR(36) NOT NULL UNIQUE,
            funnel_id INT NULL,
            member_id INT NULL,
            product_id INT NULL,
            offer_id INT NULL,
            source_platform VARCHAR(50) NOT NULL,
            source_event VARCHAR(100) NULL,
            transaction_id VARCHAR(191) NULL,
            order_reference VARCHAR(191) NULL,
            customer_name VARCHAR(200) NULL,
            customer_email VARCHAR(191) NULL,
            customer_document VARCHAR(20) NULL,
            customer_document_type ENUM('cpf','cnpj','foreign','unknown') NOT NULL DEFAULT 'unknown',
            customer_phone VARCHAR(30) NULL,
            customer_zip VARCHAR(12) NULL,
            customer_street VARCHAR(200) NULL,
            customer_number VARCHAR(30) NULL,
            customer_complement VARCHAR(100) NULL,
            customer_district VARCHAR(100) NULL,
            customer_municipality_code VARCHAR(10) NULL,
            customer_city VARCHAR(100) NULL,
            customer_state CHAR(2) NULL,
            customer_country VARCHAR(2) NULL DEFAULT 'BR',
            amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            currency CHAR(3) NOT NULL DEFAULT 'BRL',
            status ENUM('pending','paid','refunded','canceled') NOT NULL DEFAULT 'paid',
            invoice_status ENUM('not_issued','pending','issued','error','rejected','canceled') NOT NULL DEFAULT 'not_issued',
            payload JSON NULL,
            paid_at DATETIME NULL,
            refunded_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_funnel (funnel_id),
            INDEX idx_member (member_id),
            INDEX idx_product (product_id),
            INDEX idx_offer (offer_id),
            INDEX idx_transaction (source_platform, transaction_id),
            INDEX idx_status (status),
            INDEX idx_invoice_status (invoice_status),
            INDEX idx_paid_at (paid_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    'fiscal_invoices' => "
        CREATE TABLE IF NOT EXISTS fiscal_invoices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sale_id INT NOT NULL,
            provider VARCHAR(50) NOT NULL DEFAULT 'nfse_nacional',
            environment ENUM('restricted','production') NOT NULL DEFAULT 'restricted',
            document_type ENUM('nfse') NOT NULL DEFAULT 'nfse',
            status ENUM('draft','processing','issued','rejected','canceled','cancel_error') NOT NULL DEFAULT 'draft',
            dps_series VARCHAR(20) NULL,
            dps_number INT NULL,
            dps_id VARCHAR(80) NULL,
            access_key VARCHAR(80) NULL,
            verification_code VARCHAR(80) NULL,
            issued_at DATETIME NULL,
            canceled_at DATETIME NULL,
            cancel_reason VARCHAR(255) NULL,
            total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            tax_group_id INT NULL,
            tax_rule_id INT NULL,
            service_code VARCHAR(30) NULL,
            municipal_service_code VARCHAR(50) NULL,
            lc116_code VARCHAR(30) NULL,
            cnae_code VARCHAR(20) NULL,
            nbs_code VARCHAR(30) NULL,
            iss_rate DECIMAL(7,4) NULL,
            service_description TEXT NULL,
            tax_snapshot JSON NULL,
            xml_path VARCHAR(500) NULL,
            pdf_path VARCHAR(500) NULL,
            request_payload JSON NULL,
            response_payload JSON NULL,
            errors TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_sale (sale_id),
            INDEX idx_status (status),
            INDEX idx_access_key (access_key),
            INDEX idx_dps (dps_series, dps_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    'fiscal_tax_groups' => "
        CREATE TABLE IF NOT EXISTS fiscal_tax_groups (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            document_model ENUM('nfse','nfe') NOT NULL DEFAULT 'nfse',
            is_default TINYINT NOT NULL DEFAULT 0,
            settings_json JSON NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_model (document_model),
            INDEX idx_default (document_model, is_default)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    'fiscal_tax_rules' => "
        CREATE TABLE IF NOT EXISTS fiscal_tax_rules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tax_group_id INT NOT NULL,
            name VARCHAR(120) NOT NULL,
            customer_location ENUM('any','same_city','outside_city','foreign') NOT NULL DEFAULT 'any',
            customer_type ENUM('any','cpf','cnpj','foreign') NOT NULL DEFAULT 'any',
            operation_nature VARCHAR(50) NULL,
            iss_rate DECIMAL(7,4) NULL,
            pis_rate DECIMAL(7,4) NULL,
            cofins_rate DECIMAL(7,4) NULL,
            ir_rate DECIMAL(7,4) NULL,
            inss_rate DECIMAL(7,4) NULL,
            csll_rate DECIMAL(7,4) NULL,
            deduction_rate DECIMAL(7,4) NULL,
            retain_iss TINYINT NOT NULL DEFAULT 0,
            service_location ENUM('company_city','customer_city','custom') NOT NULL DEFAULT 'company_city',
            incidence_municipality_type ENUM('company_city','customer_city','custom') NOT NULL DEFAULT 'company_city',
            incidence_municipality_code VARCHAR(10) NULL,
            cst VARCHAR(20) NULL,
            municipal_benefit VARCHAR(50) NULL,
            additional_info TEXT NULL,
            conditions_json JSON NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_group (tax_group_id),
            INDEX idx_location (customer_location, customer_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    'fiscal_closings' => "
        CREATE TABLE IF NOT EXISTS fiscal_closings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            year INT NOT NULL,
            month TINYINT NOT NULL,
            status ENUM('draft','generated','sent','error') NOT NULL DEFAULT 'draft',
            include_xml TINYINT NOT NULL DEFAULT 1,
            include_pdf TINYINT NOT NULL DEFAULT 0,
            include_xlsx TINYINT NOT NULL DEFAULT 1,
            block_after_closing TINYINT NOT NULL DEFAULT 0,
            email_to VARCHAR(191) NULL,
            total_authorized INT NOT NULL DEFAULT 0,
            total_canceled INT NOT NULL DEFAULT 0,
            total_refunded INT NOT NULL DEFAULT 0,
            total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            file_path VARCHAR(500) NULL,
            generated_at DATETIME NULL,
            notes TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_period (year, month),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    'push_subscriptions' => "
        CREATE TABLE IF NOT EXISTS push_subscriptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            member_id INT NOT NULL,
            funnel_id INT NOT NULL,
            endpoint TEXT NOT NULL,
            p256dh VARCHAR(255) NOT NULL,
            auth_key VARCHAR(255) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_funnel (funnel_id),
            INDEX idx_member (member_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    'push_notifications' => "
        CREATE TABLE IF NOT EXISTS push_notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            funnel_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            body TEXT NOT NULL,
            url VARCHAR(500) NULL,
            sent_count INT NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_funnel (funnel_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    'support_contacts' => "
        CREATE TABLE IF NOT EXISTS support_contacts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(191) NOT NULL UNIQUE,
            name VARCHAR(200) NULL,
            phone VARCHAR(30) NULL,
            member_id INT NULL,
            last_funnel_id INT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_member (member_id),
            INDEX idx_last_funnel (last_funnel_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    'support_tickets' => "
        CREATE TABLE IF NOT EXISTS support_tickets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ticket_number VARCHAR(32) NOT NULL UNIQUE,
            support_contact_id INT NOT NULL,
            member_id INT NULL,
            funnel_id INT NULL,
            subject VARCHAR(255) NOT NULL,
            status ENUM('open','waiting_support','waiting_customer','closed') NOT NULL DEFAULT 'open',
            source ENUM('public','member','admin') NOT NULL DEFAULT 'public',
            secure_token CHAR(64) NOT NULL UNIQUE,
            last_message_at DATETIME NULL,
            last_customer_message_at DATETIME NULL,
            last_admin_message_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_contact (support_contact_id),
            INDEX idx_member (member_id),
            INDEX idx_funnel (funnel_id),
            INDEX idx_status (status),
            INDEX idx_last_message (last_message_at),
            INDEX idx_token (secure_token)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    'support_messages' => "
        CREATE TABLE IF NOT EXISTS support_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ticket_id INT NOT NULL,
            sender_type ENUM('customer','admin') NOT NULL,
            sender_name VARCHAR(200) NULL,
            sender_email VARCHAR(191) NULL,
            admin_id INT NULL,
            message TEXT NOT NULL,
            is_internal TINYINT NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ticket (ticket_id),
            INDEX idx_sender (sender_type),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    'generations' => "
        CREATE TABLE IF NOT EXISTS generations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            funnel_id INT NOT NULL,
            site_name VARCHAR(200) NOT NULL,
            theme ENUM('elegante-escuro', 'elegante-claro', 'moderno-azul', 'moderno-verde', 'premium-dourado', 'minimalista') NOT NULL,
            html_file VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_funnel (funnel_id),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    // Tabela de Níveis de Acesso
    'access_levels' => "
        CREATE TABLE IF NOT EXISTS access_levels (
            id INT AUTO_INCREMENT PRIMARY KEY,
            funnel_id INT NOT NULL,
            name VARCHAR(200) NOT NULL,
            uuid_key CHAR(16) NOT NULL UNIQUE,
            password VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_funnel (funnel_id),
            INDEX idx_uuid_key (uuid_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    // Tabela de Produtos por Nível de Acesso
    'access_level_products' => "
        CREATE TABLE IF NOT EXISTS access_level_products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            access_level_id INT NOT NULL,
            product_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_level_product (access_level_id, product_id),
            INDEX idx_access_level (access_level_id),
            INDEX idx_product (product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    "
];
