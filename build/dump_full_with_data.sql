--
-- PostgreSQL database dump
--

\restrict lVCVmYhaesRVdF0eA29zgUq4wQeVUm1Izsqt5ZaMgTitvqe0EHefjcUrfkVoymw

-- Dumped from database version 18.1
-- Dumped by pg_dump version 18.1

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: auth; Type: SCHEMA; Schema: -; Owner: -
--

CREATE SCHEMA auth;


--
-- Name: postgis; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS postgis WITH SCHEMA public;


--
-- Name: EXTENSION postgis; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION postgis IS 'PostGIS geometry and geography spatial types and functions';


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: contracts; Type: TABLE; Schema: auth; Owner: -
--

CREATE TABLE auth.contracts (
    id bigint NOT NULL,
    organization_id bigint NOT NULL,
    status text NOT NULL,
    start_date date,
    end_date date,
    CONSTRAINT contracts_status_check CHECK ((status = ANY (ARRAY['active'::text, 'expired'::text])))
);


--
-- Name: contracts_id_seq; Type: SEQUENCE; Schema: auth; Owner: -
--

CREATE SEQUENCE auth.contracts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contracts_id_seq; Type: SEQUENCE OWNED BY; Schema: auth; Owner: -
--

ALTER SEQUENCE auth.contracts_id_seq OWNED BY auth.contracts.id;


--
-- Name: organizations; Type: TABLE; Schema: auth; Owner: -
--

CREATE TABLE auth.organizations (
    id bigint NOT NULL,
    name text NOT NULL,
    org_type text NOT NULL,
    CONSTRAINT organizations_org_type_check CHECK ((org_type = ANY (ARRAY['key_partner'::text, 'potential_customer'::text])))
);


--
-- Name: organizations_id_seq; Type: SEQUENCE; Schema: auth; Owner: -
--

CREATE SEQUENCE auth.organizations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: organizations_id_seq; Type: SEQUENCE OWNED BY; Schema: auth; Owner: -
--

ALTER SEQUENCE auth.organizations_id_seq OWNED BY auth.organizations.id;


--
-- Name: roles; Type: TABLE; Schema: auth; Owner: -
--

CREATE TABLE auth.roles (
    id bigint NOT NULL,
    name text NOT NULL
);


--
-- Name: roles_id_seq; Type: SEQUENCE; Schema: auth; Owner: -
--

CREATE SEQUENCE auth.roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: roles_id_seq; Type: SEQUENCE OWNED BY; Schema: auth; Owner: -
--

ALTER SEQUENCE auth.roles_id_seq OWNED BY auth.roles.id;


--
-- Name: user_organizations; Type: TABLE; Schema: auth; Owner: -
--

CREATE TABLE auth.user_organizations (
    user_id bigint NOT NULL,
    organization_id bigint NOT NULL
);


--
-- Name: user_roles; Type: TABLE; Schema: auth; Owner: -
--

CREATE TABLE auth.user_roles (
    user_id bigint NOT NULL,
    role_id bigint NOT NULL
);


--
-- Name: users; Type: TABLE; Schema: auth; Owner: -
--

CREATE TABLE auth.users (
    id bigint NOT NULL,
    email text NOT NULL,
    password_hash text NOT NULL,
    full_name text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone
);


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: auth; Owner: -
--

CREATE SEQUENCE auth.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: auth; Owner: -
--

ALTER SEQUENCE auth.users_id_seq OWNED BY auth.users.id;


--
-- Name: cba_l1_id; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cba_l1_id (
    id integer NOT NULL,
    line_name text,
    value_cba numeric,
    geom public.geometry(Point,4326)
);


--
-- Name: cba_l1_id_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cba_l1_id_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cba_l1_id_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cba_l1_id_id_seq OWNED BY public.cba_l1_id.id;


--
-- Name: geo_datasets; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.geo_datasets (
    id integer NOT NULL,
    title text NOT NULL,
    country_code character varying(2) DEFAULT 'ID'::character varying,
    country_name text DEFAULT 'Indonesia'::text,
    is_downloadable boolean DEFAULT true,
    is_viewable boolean DEFAULT true,
    spatial_scope character varying(20) NOT NULL,
    download_url text,
    view_url text,
    created_at timestamp without time zone DEFAULT now(),
    backend_type character varying(10) DEFAULT 'table'::character varying,
    data_schema text DEFAULT 'public'::text,
    data_table text,
    geom_column text DEFAULT 'geom'::text
);


--
-- Name: geo_datasets_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.geo_datasets_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: geo_datasets_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.geo_datasets_id_seq OWNED BY public.geo_datasets.id;


--
-- Name: gravity_level1_points; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.gravity_level1_points (
    objectid integer NOT NULL,
    namobj character varying(100),
    kjkgn character varying(20),
    wktgbt timestamp without time zone,
    mtdp character varying(100),
    koordy double precision,
    koordx double precision,
    helips double precision,
    hortho double precision,
    gybrt double precision,
    tgf double precision,
    faa double precision,
    cba double precision,
    std double precision,
    remark text,
    geom public.geometry(Point,4326)
);


--
-- Name: gravity_level1_points_objectid_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.gravity_level1_points_objectid_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: gravity_level1_points_objectid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.gravity_level1_points_objectid_seq OWNED BY public.gravity_level1_points.objectid;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id bigint NOT NULL,
    version character varying(255) NOT NULL,
    class character varying(255) NOT NULL,
    "group" character varying(255) NOT NULL,
    namespace character varying(255) NOT NULL,
    "time" integer NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    username character varying(80) NOT NULL,
    email character varying(160) NOT NULL,
    password_hash text NOT NULL,
    role character varying(20) DEFAULT 'user'::character varying NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp without time zone,
    updated_at timestamp without time zone
);


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: contracts id; Type: DEFAULT; Schema: auth; Owner: -
--

ALTER TABLE ONLY auth.contracts ALTER COLUMN id SET DEFAULT nextval('auth.contracts_id_seq'::regclass);


--
-- Name: organizations id; Type: DEFAULT; Schema: auth; Owner: -
--

ALTER TABLE ONLY auth.organizations ALTER COLUMN id SET DEFAULT nextval('auth.organizations_id_seq'::regclass);


--
-- Name: roles id; Type: DEFAULT; Schema: auth; Owner: -
--

ALTER TABLE ONLY auth.roles ALTER COLUMN id SET DEFAULT nextval('auth.roles_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: auth; Owner: -
--

ALTER TABLE ONLY auth.users ALTER COLUMN id SET DEFAULT nextval('auth.users_id_seq'::regclass);


--
-- Name: cba_l1_id id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cba_l1_id ALTER COLUMN id SET DEFAULT nextval('public.cba_l1_id_id_seq'::regclass);


--
-- Name: geo_datasets id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.geo_datasets ALTER COLUMN id SET DEFAULT nextval('public.geo_datasets_id_seq'::regclass);


--
-- Name: gravity_level1_points objectid; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.gravity_level1_points ALTER COLUMN objectid SET DEFAULT nextval('public.gravity_level1_points_objectid_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Data for Name: contracts; Type: TABLE DATA; Schema: auth; Owner: -
--



--
-- Data for Name: organizations; Type: TABLE DATA; Schema: auth; Owner: -
--



--
-- Data for Name: roles; Type: TABLE DATA; Schema: auth; Owner: -
--

INSERT INTO auth.roles VALUES (1, 'admin');
INSERT INTO auth.roles VALUES (2, 'user');


--
-- Data for Name: user_organizations; Type: TABLE DATA; Schema: auth; Owner: -
--



--
-- Data for Name: user_roles; Type: TABLE DATA; Schema: auth; Owner: -
--

INSERT INTO auth.user_roles VALUES (2, 2);
INSERT INTO auth.user_roles VALUES (1, 1);
INSERT INTO auth.user_roles VALUES (18, 2);


--
-- Data for Name: users; Type: TABLE DATA; Schema: auth; Owner: -
--

INSERT INTO auth.users VALUES (1, 'admin@gravport.test', '$2y$10$2toA8QUz7krpCfeaVwkgS.dIxYMmS0V0413dBDZSDqfulBb.fSoui', 'Super Admin', true, '2026-02-06 16:14:33.317766', NULL);
INSERT INTO auth.users VALUES (2, 'client@gravport.test', '$2y$10$keZ3yvWKitEzmn9ivYz8uegG/VzpbQCmbDai4ofYu9j1JjB10TIXm', 'Client Demo', true, '2026-02-06 16:15:38.296846', NULL);
INSERT INTO auth.users VALUES (18, 'chanwaltz0404@gmail.com', '$2a$12$PA4CXYCgTgEYBYrDTYI0juogsZt54vxZN1mOq.A.ov4WZfpu/soqG', 'Chan Waltz', true, '2026-05-09 19:29:03.720781', '2026-05-09 19:29:03.720781');


--
-- Data for Name: cba_l1_id; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.cba_l1_id VALUES (1, 'LRT Segment 1', 10.5, '0101000020E6100000D95F764F1EB65A40849ECDAACFD518C0');
INSERT INTO public.cba_l1_id VALUES (2, 'LRT Segment 2', 12.3, '0101000020E610000014D044D8F0B45A40A4DFBE0E9CB318C0');
INSERT INTO public.cba_l1_id VALUES (3, 'LRT Segment 3', 9.8, '0101000020E6100000083D9B559FE75A40EC51B81E85AB1BC0');
INSERT INTO public.cba_l1_id VALUES (4, 'LRT Segment 4', 11.1, '0101000020E6100000CFF753E3A5975B4058CA32C4B12E1FC0');
INSERT INTO public.cba_l1_id VALUES (5, 'LRT Segment 5', 13.0, '0101000020E61000008E75711B0D305C4048E17A14AE071DC0');


--
-- Data for Name: geo_datasets; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.geo_datasets VALUES (2, 'Scatter FAA Level 1 - DKI Jakarta', 'ID', 'Indonesia', true, true, 'regional', 'https://example.com/download/faa_l1_jakarta', 'https://example.com/view/faa_l1_jakarta', '2025-12-06 13:14:04.174737', 'table', 'public', 'cba_l1_id', 'geom');
INSERT INTO public.geo_datasets VALUES (3, 'Scatter CBA Level 1 - Indonesia', 'ID', 'Indonesia', true, true, 'national', 'https://example.com/download/cba_l1_id', 'https://example.com/view/cba_l1_id', '2025-12-06 13:14:04.174737', 'table', 'public', 'cba_l1_id', 'geom');
INSERT INTO public.geo_datasets VALUES (1, 'Scatter FAA Level 1 - Indonesia', 'ID', 'Indonesia', true, true, 'national', 'https://example.com/download/faa_l1_id', 'https://example.com/view/faa_l1_id', '2025-12-06 13:14:04.174737', 'table', 'public', 'cba_l1_id', 'geom');


--
-- Data for Name: gravity_level1_points; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.migrations VALUES (1, '2026-01-29-170151', 'App\Database\Migrations\CreateUsersTable', 'mockup', 'App', 1769706868, 1);


--
-- Data for Name: spatial_ref_sys; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.users VALUES (1, 'superadmin', 'admin@gravport.test', '$2y$10$96GRUbSLX64NVoCvQal0auM7G/p9WA5W9jMuFF3t.HddlIGqMdieS', 'admin', true, '2026-01-29 17:19:49', '2026-01-29 17:19:49');
INSERT INTO public.users VALUES (2, 'client_demo', 'client@gravport.test', '$2y$10$FnhSIny.TfhMdCArSjIXkuFrakffM7Jr45h4qRU0ugOhsb5xoK1rS', 'user', true, '2026-01-29 17:19:49', '2026-01-29 17:19:49');


--
-- Name: contracts_id_seq; Type: SEQUENCE SET; Schema: auth; Owner: -
--

SELECT pg_catalog.setval('auth.contracts_id_seq', 1, false);


--
-- Name: organizations_id_seq; Type: SEQUENCE SET; Schema: auth; Owner: -
--

SELECT pg_catalog.setval('auth.organizations_id_seq', 1, false);


--
-- Name: roles_id_seq; Type: SEQUENCE SET; Schema: auth; Owner: -
--

SELECT pg_catalog.setval('auth.roles_id_seq', 2, true);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: auth; Owner: -
--

SELECT pg_catalog.setval('auth.users_id_seq', 30, true);


--
-- Name: cba_l1_id_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.cba_l1_id_id_seq', 5, true);


--
-- Name: geo_datasets_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.geo_datasets_id_seq', 3, true);


--
-- Name: gravity_level1_points_objectid_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.gravity_level1_points_objectid_seq', 1, false);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.migrations_id_seq', 1, true);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.users_id_seq', 2, true);


--
-- Name: contracts contracts_pkey; Type: CONSTRAINT; Schema: auth; Owner: -
--

ALTER TABLE ONLY auth.contracts
    ADD CONSTRAINT contracts_pkey PRIMARY KEY (id);


--
-- Name: organizations organizations_pkey; Type: CONSTRAINT; Schema: auth; Owner: -
--

ALTER TABLE ONLY auth.organizations
    ADD CONSTRAINT organizations_pkey PRIMARY KEY (id);


--
-- Name: roles roles_name_key; Type: CONSTRAINT; Schema: auth; Owner: -
--

ALTER TABLE ONLY auth.roles
    ADD CONSTRAINT roles_name_key UNIQUE (name);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: auth; Owner: -
--

ALTER TABLE ONLY auth.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id);


--
-- Name: user_organizations user_organizations_pkey; Type: CONSTRAINT; Schema: auth; Owner: -
--

ALTER TABLE ONLY auth.user_organizations
    ADD CONSTRAINT user_organizations_pkey PRIMARY KEY (user_id, organization_id);


--
-- Name: user_roles user_roles_pkey; Type: CONSTRAINT; Schema: auth; Owner: -
--

ALTER TABLE ONLY auth.user_roles
    ADD CONSTRAINT user_roles_pkey PRIMARY KEY (user_id, role_id);


--
-- Name: users users_email_key; Type: CONSTRAINT; Schema: auth; Owner: -
--

ALTER TABLE ONLY auth.users
    ADD CONSTRAINT users_email_key UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: auth; Owner: -
--

ALTER TABLE ONLY auth.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: cba_l1_id cba_l1_id_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cba_l1_id
    ADD CONSTRAINT cba_l1_id_pkey PRIMARY KEY (id);


--
-- Name: geo_datasets geo_datasets_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.geo_datasets
    ADD CONSTRAINT geo_datasets_pkey PRIMARY KEY (id);


--
-- Name: gravity_level1_points gravity_level1_points_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.gravity_level1_points
    ADD CONSTRAINT gravity_level1_points_pkey PRIMARY KEY (objectid);


--
-- Name: migrations pk_migrations; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT pk_migrations PRIMARY KEY (id);


--
-- Name: users pk_users; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT pk_users PRIMARY KEY (id);


--
-- Name: users users_email; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email UNIQUE (email);


--
-- Name: contracts contracts_organization_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: -
--

ALTER TABLE ONLY auth.contracts
    ADD CONSTRAINT contracts_organization_id_fkey FOREIGN KEY (organization_id) REFERENCES auth.organizations(id) ON DELETE CASCADE;


--
-- Name: user_organizations user_organizations_organization_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: -
--

ALTER TABLE ONLY auth.user_organizations
    ADD CONSTRAINT user_organizations_organization_id_fkey FOREIGN KEY (organization_id) REFERENCES auth.organizations(id) ON DELETE CASCADE;


--
-- Name: user_organizations user_organizations_user_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: -
--

ALTER TABLE ONLY auth.user_organizations
    ADD CONSTRAINT user_organizations_user_id_fkey FOREIGN KEY (user_id) REFERENCES auth.users(id) ON DELETE CASCADE;


--
-- Name: user_roles user_roles_role_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: -
--

ALTER TABLE ONLY auth.user_roles
    ADD CONSTRAINT user_roles_role_id_fkey FOREIGN KEY (role_id) REFERENCES auth.roles(id) ON DELETE CASCADE;


--
-- Name: user_roles user_roles_user_id_fkey; Type: FK CONSTRAINT; Schema: auth; Owner: -
--

ALTER TABLE ONLY auth.user_roles
    ADD CONSTRAINT user_roles_user_id_fkey FOREIGN KEY (user_id) REFERENCES auth.users(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict lVCVmYhaesRVdF0eA29zgUq4wQeVUm1Izsqt5ZaMgTitvqe0EHefjcUrfkVoymw

