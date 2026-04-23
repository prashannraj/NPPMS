-- Nepal Public Procurement Management System (NPPMS) Database Schema
-- PostgreSQL 16
-- Generated: 2081-01-01 BS (2024-04-23 AD)

-- Enable UUID extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- =============================================
-- MODULE 1: CORE SYSTEM TABLES
-- =============================================

-- 1.1 Federal Structure
CREATE TABLE provinces (
    id BIGSERIAL PRIMARY KEY,
    uuid UUID UNIQUE DEFAULT gen_random_uuid(),
    name_np VARCHAR(255) NOT NULL,        -- प्रदेश नाम (नेपाली)
    name_en VARCHAR(255) NOT NULL,        -- Province Name (English)
    code VARCHAR(10) UNIQUE NOT NULL,     -- Province Code
    capital_np VARCHAR(255),
    capital_en VARCHAR(255),
    is_active BOOLEAN DEFAULT true,
    created_by BIGINT REFERENCES users(id),
    updated_by BIGINT REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

CREATE TABLE districts (
    id BIGSERIAL PRIMARY KEY,
    uuid UUID UNIQUE DEFAULT gen_random_uuid(),
    province_id BIGINT REFERENCES provinces(id),
    name_np VARCHAR(255) NOT NULL,
    name_en VARCHAR(255) NOT NULL,
    code VARCHAR(10) UNIQUE NOT NULL,
    is_active BOOLEAN DEFAULT true,
    created_by BIGINT,
    updated_by BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

-- Create ENUM type for local body types
CREATE TYPE local_body_type AS ENUM (
    'municipality', 
    'rural_municipality', 
    'sub_metropolitan', 
    'metropolitan'
);

CREATE TABLE local_bodies (
    id BIGSERIAL PRIMARY KEY,
    uuid UUID UNIQUE DEFAULT gen_random_uuid(),
    district_id BIGINT REFERENCES districts(id),
    name_np VARCHAR(255) NOT NULL,
    name_en VARCHAR(255) NOT NULL,
    type local_body_type NOT NULL,
    -- type: नगरपालिका, गाउँपालिका, उपमहानगरपालिका, महानगरपालिका
    code VARCHAR(20) UNIQUE NOT NULL,
    total_wards INTEGER NOT NULL,
    address_np TEXT,
    address_en TEXT,
    phone VARCHAR(20),
    email VARCHAR(255),
    website VARCHAR(255),
    logo_path VARCHAR(500),
    is_active BOOLEAN DEFAULT true,
    created_by BIGINT,
    updated_by BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

CREATE TABLE wards (
    id BIGSERIAL PRIMARY KEY,
    uuid UUID UNIQUE DEFAULT gen_random_uuid(),
    local_body_id BIGINT REFERENCES local_bodies(id),
    ward_number INTEGER NOT NULL,
    ward_chairperson_name VARCHAR(255),
    phone VARCHAR(20),
    is_active BOOLEAN DEFAULT true,
    created_by BIGINT,
    updated_by BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP,
    UNIQUE(local_body_id, ward_number)
);

-- 1.2 Fiscal Year
CREATE TABLE fiscal_years (
    id BIGSERIAL PRIMARY KEY,
    uuid UUID UNIQUE DEFAULT gen_random_uuid(),
    name_np VARCHAR(50) NOT NULL,           -- e.g., "२०८१/८२"
    name_en VARCHAR(50) NOT NULL,           -- e.g., "2081/82"
    start_date_bs VARCHAR(10) NOT NULL,     -- BS date "2081-04-01"
    end_date_bs VARCHAR(10) NOT NULL,       -- BS date "2082-03-31"
    start_date_ad DATE NOT NULL,
    end_date_ad DATE NOT NULL,
    is_current BOOLEAN DEFAULT false,
    is_active BOOLEAN DEFAULT true,
    created_by BIGINT,
    updated_by BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

-- 1.3 Users & Roles (RBAC)
-- Create ENUM type for role levels
CREATE TYPE role_level AS ENUM ('system', 'province', 'local_body', 'ward');

CREATE TABLE roles (
    id BIGSERIAL PRIMARY KEY,
    uuid UUID UNIQUE DEFAULT gen_random_uuid(),
    name VARCHAR(100) UNIQUE NOT NULL,
    display_name_np VARCHAR(255) NOT NULL,
    display_name_en VARCHAR(255) NOT NULL,
    description TEXT,
    level role_level NOT NULL,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

CREATE TABLE permissions (
    id BIGSERIAL PRIMARY KEY,
    uuid UUID UNIQUE DEFAULT gen_random_uuid(),
    name VARCHAR(255) UNIQUE NOT NULL,
    display_name_np VARCHAR(255),
    display_name_en VARCHAR(255),
    module VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE role_permissions (
    id BIGSERIAL PRIMARY KEY,
    role_id BIGINT REFERENCES roles(id),
    permission_id BIGINT REFERENCES permissions(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(role_id, permission_id)
);

CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    uuid UUID UNIQUE DEFAULT gen_random_uuid(),
    name_np VARCHAR(255) NOT NULL,
    name_en VARCHAR(255),
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    province_id BIGINT REFERENCES provinces(id),
    local_body_id BIGINT REFERENCES local_bodies(id),
    ward_id BIGINT REFERENCES wards(id),
    designation_np VARCHAR(255),
    designation_en VARCHAR(255),
    profile_photo VARCHAR(500),
    is_active BOOLEAN DEFAULT true,
    email_verified_at TIMESTAMP,
    remember_token VARCHAR(100),
    last_login_at TIMESTAMP,
    last_login_ip VARCHAR(45),
    created_by BIGINT,
    updated_by BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

CREATE TABLE user_roles (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT REFERENCES users(id),
    role_id BIGINT REFERENCES roles(id),
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_by BIGINT REFERENCES users(id),
    revoked_at TIMESTAMP,
    revoked_by BIGINT,
    is_active BOOLEAN DEFAULT true,
    UNIQUE(user_id, role_id)
);

-- 1.4 Legal References
-- Create ENUM type for reference types
CREATE TYPE legal_reference_type AS ENUM ('act', 'regulation', 'directive', 'circular');
CREATE TYPE legal_applicable_to AS ENUM ('bidding', 'consumer_committee', 'both');

CREATE TABLE legal_references (
    id BIGSERIAL PRIMARY KEY,
    uuid UUID UNIQUE DEFAULT gen_random_uuid(),
    act_name_np VARCHAR(500) NOT NULL,       -- ऐनको नाम
    act_name_en VARCHAR(500),
    act_year_bs VARCHAR(10),                 -- ऐन वर्ष (बि.सं.)
    reference_type legal_reference_type NOT NULL,
    section_number VARCHAR(50),              -- दफा नं.
    sub_section VARCHAR(50),                 -- उपदफा
    rule_number VARCHAR(50),                 -- नियम नं.
    sub_rule VARCHAR(50),                    -- उपनियम
    clause VARCHAR(50),                      -- खण्ड
    title_np TEXT NOT NULL,                  -- शीर्षक
    title_en TEXT,
    content_np TEXT NOT NULL,                -- विषयवस्तु
    content_en TEXT,
    applicable_to legal_applicable_to DEFAULT 'both',
    applicable_step VARCHAR(100),            -- Which process step
    is_active BOOLEAN DEFAULT true,
    created_by BIGINT,
    updated_by BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

-- 1.5 Notification System
-- Create ENUM types for notification channels and priorities
CREATE TYPE notification_channel AS ENUM ('web', 'email', 'sms', 'push');
CREATE TYPE notification_priority AS ENUM ('low', 'normal', 'high', 'urgent');

CREATE TABLE notifications (
    id BIGSERIAL PRIMARY KEY,
    uuid UUID UNIQUE DEFAULT gen_random_uuid(),
    user_id BIGINT REFERENCES users(id),
    type VARCHAR(255) NOT NULL,
    title_np TEXT NOT NULL,
    title_en TEXT,
    message_np TEXT NOT NULL,
    message_en TEXT,
    data JSONB,                              -- Additional context data
    related_model VARCHAR(255),              -- Polymorphic relation
    related_id BIGINT,
    channel notification_channel DEFAULT 'web',
    priority notification_priority DEFAULT 'normal',
    read_at TIMESTAMP,
    sent_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 1.6 Audit Trail
-- Create ENUM type for audit actions
CREATE TYPE audit_action AS ENUM (
    'create', 'update', 'delete', 'view', 
    'approve', 'reject', 'submit', 'download', 'print'
);

CREATE TABLE audit_logs (
    id BIGSERIAL PRIMARY KEY,
    uuid UUID UNIQUE DEFAULT gen_random_uuid(),
    user_id BIGINT REFERENCES users(id),
    action audit_action NOT NULL,
    model_type VARCHAR(255) NOT NULL,
    model_id BIGINT NOT NULL,
    old_values JSONB,
    new_values JSONB,
    ip_address VARCHAR(45),
    user_agent TEXT,
    description_np TEXT,
    description_en TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 1.7 District Rate (जिल्ला दररेट)
CREATE TABLE district_rates (
    id BIGSERIAL PRIMARY KEY,
    uuid UUID UNIQUE DEFAULT gen_random_uuid(),
    district_id BIGINT REFERENCES districts(id),
    fiscal_year_id BIGINT REFERENCES fiscal_years(id),
    item_code VARCHAR(50),
    item_name_np VARCHAR(500) NOT NULL,
    item_name_en VARCHAR(500),
    unit VARCHAR(50) NOT NULL,
    rate DECIMAL(15,2) NOT NULL,
    category VARCHAR(100),
    is_active BOOLEAN DEFAULT true,
    created_by BIGINT,
    updated_by BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

-- =============================================
-- MODULE 2: BUDGET & PLANNING TABLES
-- =============================================

-- 2.1 Budget Heads
CREATE TABLE budget_heads (
    id BIGSERIAL PRIMARY KEY,
    uuid UUID UNIQUE DEFAULT gen_random_uuid(),
    local_body_id BIGINT REFERENCES local_bodies(id),
    fiscal_year_id BIGINT REFERENCES fiscal_years(id),
    head_code VARCHAR(50) NOT NULL,
    head_name_np VARCHAR(500) NOT NULL,
    head_name_en VARCHAR(500),
    sub_head_code VARCHAR(50),
    sub_head_name_np VARCHAR(500),
    allocated_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
    spent_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
    remaining_amount DECIMAL(18,2) GENERATED ALWAYS AS 
        (allocated_amount - spent_amount) STORED,
    is_active BOOLEAN DEFAULT true,
    created_by BIGINT,
    updated_by BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

-- 2.2 Plans/Projects (योजना)
-- Create ENUM types for project types, procurement methods, and status
CREATE TYPE project_type AS ENUM ('construction', 'goods', 'services', 'consulting_services');
CREATE TYPE procurement_method AS ENUM (
    'bidding', 'consumer_committee', 'sealed_quotation', 
    'direct_purchase', 'force_account'
);
CREATE TYPE project_status AS ENUM (
    'draft',
    'ward_proposed',
    'bpfc_recommended',
    'executive_approved',
    'assembly_approved',
    'estimate_prepared',
    'estimate_approved',
    'procurement_planned',
    'in_procurement',
    'contracted',
    'in_progress',
    'completed',
    'inspected',
    'final_payment_done',
    'closed'
);

CREATE TABLE projects (
    id BIGSERIAL PRIMARY KEY,
    uuid UUID UNIQUE DEFAULT gen_random_uuid(),
    local_body_id BIGINT REFERENCES local_bodies(id),
    fiscal_year_id BIGINT REFERENCES fiscal_years(id),
    ward_id BIGINT REFERENCES wards(id),
    budget_head_id BIGINT REFERENCES budget_heads(id),
    
    project_code VARCHAR(50) UNIQUE,
    project_name_np VARCHAR(500) NOT NULL,
    project_name_en VARCHAR(500),
    project_description_np TEXT,
    project_description_en TEXT,
    project_type project_type NOT NULL,
    
    -- Location
    location_np VARCHAR(500),
    location_en VARCHAR(500),
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    
    -- Financial
    estimated_cost DECIMAL(18,2) NOT NULL,
    approved_budget DECIMAL(18,2),
    
    -- Procurement Method
    procurement_method procurement_method NOT NULL,
    
    -- Priority & Status
    priority_rank INTEGER,
    
    -- Workflow Status
    status project_status DEFAULT 'draft',
    
    current_step VARCHAR(100),
    
    -- Source selection
    ward_meeting_date_bs VARCHAR(10),
    executive_approval_date_bs VARCHAR(10),
    assembly_approval_date_bs VARCHAR(10),
    
    -- Beneficiary info (for consumer committee)
    total_beneficiary_households INTEGER,
    total_beneficiary_population INTEGER,
    
    -- Timestamps (BS)
    created_date_bs VARCHAR(10),
    
    is_active BOOLEAN DEFAULT true,
    created_by BIGINT,
    updated_by BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

-- 2.3 Ward Meeting Decisions (वडा भेला निर्णय)
-- Create ENUM type for decision status
CREATE TYPE ward_decision_status AS ENUM ('draft', 'finalized', 'submitted');

CREATE TABLE ward_meeting_decisions (
    id BIGSERIAL PRIMARY KEY,
    uuid UUID UNIQUE DEFAULT gen_random_uuid(),
    ward_id BIGINT REFERENCES wards(id),
    fiscal_year_id BIGINT REFERENCES fiscal_years(id),
    meeting_date_bs VARCHAR(10) NOT NULL,
    meeting_date_ad DATE NOT NULL,
    venue_np VARCHAR(500),
    total_attendees INTEGER,
    male_attendees INTEGER,
    female_attendees INTEGER,
    dalit_janajati_attendees INTEGER,
    decision_number VARCHAR(50),
    decision_content_np TEXT NOT NULL,
    decision_content_en TEXT,
    chairperson_name VARCHAR(255),
    chairperson_signature_path VARCHAR(500),
    attendance_sheet_path VARCHAR(500),
    status ward_decision_status DEFAULT 'draft',
    created_by BIGINT,
    updated_by BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

CREATE TABLE ward_meeting_projects (
    id BIGSERIAL PRIMARY KEY,
    ward_meeting_decision_id BIGINT REFERENCES ward_meeting_decisions(id),
    project_id BIGINT REFERENCES projects(id),
    priority_rank INTEGER,
    estimated_cost DECIMAL(18,2),
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2.4 Executive Meeting Decisions (कार्यपालिका बैठक निर्णय)
-- Create ENUM type for executive decision status
CREATE TYPE executive_decision_status AS ENUM ('draft', 'finalized', 'approved');

CREATE TABLE executive_meeting_decisions (
    id BIGSERIAL PRIMARY KEY,
    uuid UUID UNIQUE DEFAULT gen_random_uuid(),
    local_body_id BIGINT REFERENCES local_bodies(id),
    fiscal_year_id BIGINT REFERENCES fiscal_years(id),
    meeting_number VARCHAR(50),
    meeting_date_bs VARCHAR(10) NOT NULL,
    meeting_date_ad DATE NOT NULL,
    venue_np VARCHAR(500),
    agenda_np TEXT NOT NULL,
    decision_number VARCHAR(50),
    decision_content_np TEXT NOT NULL,
    attendees JSONB,                -- List of attendees with designations
    chairperson_name VARCHAR(255),
    chairperson_designation VARCHAR(255),
    status executive_decision_status DEFAULT 'draft',
    document_path VARCHAR(500),
    created_by BIGINT,
    updated_by BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

-- 2.5 Assembly/Council Decisions (सभा/परिषद् निर्णय)
-- Create ENUM types for assembly type and status
CREATE TYPE assembly_type AS ENUM ('gaun_sabha', 'nagar_sabha');
CREATE TYPE assembly_decision_status AS ENUM ('draft', 'finalized', 'approved');

CREATE TABLE assembly_decisions (
    id BIGSERIAL PRIMARY KEY,
    uuid UUID UNIQUE DEFAULT gen_random_uuid(),
    local_body_id BIGINT REFERENCES local_bodies(id),
    fiscal_year_id BIGINT REFERENCES fiscal_years(id),
    assembly_type assembly_type NOT NULL,
    meeting_date_bs VARCHAR(10) NOT NULL,
    meeting_date_ad DATE NOT NULL,
    decision_number VARCHAR(50),
    total_budget_amount DECIMAL(18,2),
    decision_content_np TEXT NOT NULL,
    chairperson_name VARCHAR(255),
    status assembly_decision_status DEFAULT 'draft',
    document_path VARCHAR(500),
    created_by BIGINT,
    updated_by BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

-- =============================================
-- MODULE 3: COST ESTIMATE TABLES
-- =