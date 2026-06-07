--
-- PostgreSQL database dump
--

\restrict eouuGJJzdmxlgaXuLr5KDc8cnKwkPzna8GrSYDt6cNqfUwoFDnXwx1oj2EHfpm5

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
-- Name: gravport; Type: SCHEMA; Schema: -; Owner: -
--

CREATE SCHEMA gravport;


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: anomaly_gravity_point_data; Type: TABLE; Schema: gravport; Owner: -
--

CREATE TABLE gravport.anomaly_gravity_point_data (
    point_id bigint NOT NULL,
    organization_id bigint NOT NULL,
    point_value double precision NOT NULL,
    point_anom_type character varying(10) NOT NULL,
    point_obs_type character varying(20) DEFAULT 'terrestrial'::character varying NOT NULL,
    data_level smallint DEFAULT 1 NOT NULL,
    point_metadata jsonb,
    source_file text,
    geom public.geometry(Point,4326) NOT NULL,
    created_by bigint,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    status character varying(20) DEFAULT 'active'::character varying NOT NULL,
    CONSTRAINT anomaly_gravity_point_data_data_level_check CHECK ((data_level = ANY (ARRAY[1, 2]))),
    CONSTRAINT anomaly_gravity_point_data_point_anom_type_check CHECK (((point_anom_type)::text = ANY ((ARRAY['FAA'::character varying, 'CBA'::character varying, 'SBA'::character varying, 'BA'::character varying, 'RAW'::character varying])::text[]))),
    CONSTRAINT anomaly_gravity_point_data_point_obs_type_check CHECK (((point_obs_type)::text = ANY ((ARRAY['airborne'::character varying, 'terrestrial'::character varying, 'satellite'::character varying])::text[]))),
    CONSTRAINT anomaly_gravity_point_data_status_check CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'archived'::character varying, 'deprecated'::character varying])::text[])))
);


--
-- Name: anomaly_gravity_point_data_point_id_seq; Type: SEQUENCE; Schema: gravport; Owner: -
--

CREATE SEQUENCE gravport.anomaly_gravity_point_data_point_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: anomaly_gravity_point_data_point_id_seq; Type: SEQUENCE OWNED BY; Schema: gravport; Owner: -
--

ALTER SEQUENCE gravport.anomaly_gravity_point_data_point_id_seq OWNED BY gravport.anomaly_gravity_point_data.point_id;


--
-- Name: anomaly_gravity_raster_data; Type: TABLE; Schema: gravport; Owner: -
--

CREATE TABLE gravport.anomaly_gravity_raster_data (
    raster_id bigint NOT NULL,
    organization_id bigint NOT NULL,
    raster_anom_type character varying(10) NOT NULL,
    raster_resolution numeric,
    raster_metadata jsonb,
    raster_path text,
    data_level smallint DEFAULT 2 NOT NULL,
    tile_count integer,
    source_file text,
    created_by bigint,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    status character varying(20) DEFAULT 'active'::character varying NOT NULL,
    CONSTRAINT anomaly_gravity_raster_data_data_level_check CHECK ((data_level = ANY (ARRAY[1, 2]))),
    CONSTRAINT anomaly_gravity_raster_data_raster_anom_type_check CHECK (((raster_anom_type)::text = ANY ((ARRAY['FAA'::character varying, 'CBA'::character varying, 'SBA'::character varying, 'BA'::character varying])::text[]))),
    CONSTRAINT anomaly_gravity_raster_data_status_check CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'archived'::character varying, 'deprecated'::character varying])::text[])))
);


--
-- Name: anomaly_gravity_raster_data_raster_id_seq; Type: SEQUENCE; Schema: gravport; Owner: -
--

CREATE SEQUENCE gravport.anomaly_gravity_raster_data_raster_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: anomaly_gravity_raster_data_raster_id_seq; Type: SEQUENCE OWNED BY; Schema: gravport; Owner: -
--

ALTER SEQUENCE gravport.anomaly_gravity_raster_data_raster_id_seq OWNED BY gravport.anomaly_gravity_raster_data.raster_id;


--
-- Name: land_administrative_areas; Type: TABLE; Schema: gravport; Owner: -
--

CREATE TABLE gravport.land_administrative_areas (
    adm_id bigint NOT NULL,
    adm_name text NOT NULL,
    adm_level smallint DEFAULT 1 NOT NULL,
    adm_code character varying(10),
    geom public.geometry(MultiPolygon,4326),
    CONSTRAINT land_administrative_areas_adm_level_check CHECK ((adm_level = ANY (ARRAY[0, 1])))
);


--
-- Name: land_administrative_areas_adm_id_seq; Type: SEQUENCE; Schema: gravport; Owner: -
--

CREATE SEQUENCE gravport.land_administrative_areas_adm_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: land_administrative_areas_adm_id_seq; Type: SEQUENCE OWNED BY; Schema: gravport; Owner: -
--

ALTER SEQUENCE gravport.land_administrative_areas_adm_id_seq OWNED BY gravport.land_administrative_areas.adm_id;


--
-- Name: organizations; Type: TABLE; Schema: gravport; Owner: -
--

CREATE TABLE gravport.organizations (
    organization_id bigint NOT NULL,
    organization_name text NOT NULL,
    organization_email text,
    org_type character varying(30) DEFAULT 'data_partner'::character varying NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT organizations_org_type_check CHECK (((org_type)::text = ANY ((ARRAY['data_partner'::character varying, 'subscriber_gov'::character varying, 'subscriber_com'::character varying, 'subscriber_edu'::character varying, 'internal'::character varying])::text[])))
);


--
-- Name: organizations_organization_id_seq; Type: SEQUENCE; Schema: gravport; Owner: -
--

CREATE SEQUENCE gravport.organizations_organization_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: organizations_organization_id_seq; Type: SEQUENCE OWNED BY; Schema: gravport; Owner: -
--

ALTER SEQUENCE gravport.organizations_organization_id_seq OWNED BY gravport.organizations.organization_id;


--
-- Name: point_administrative_areas; Type: TABLE; Schema: gravport; Owner: -
--

CREATE TABLE gravport.point_administrative_areas (
    point_id bigint NOT NULL,
    adm_id bigint NOT NULL
);


--
-- Name: raster_administrative_areas; Type: TABLE; Schema: gravport; Owner: -
--

CREATE TABLE gravport.raster_administrative_areas (
    raster_id bigint NOT NULL,
    adm_id bigint NOT NULL
);


--
-- Name: raster_tiles; Type: TABLE; Schema: gravport; Owner: -
--

CREATE TABLE gravport.raster_tiles (
    tile_id bigint NOT NULL,
    raster_id bigint NOT NULL,
    rast public.raster NOT NULL,
    grid_geom public.geometry(Polygon,4326)
);


--
-- Name: raster_tiles_tile_id_seq; Type: SEQUENCE; Schema: gravport; Owner: -
--

CREATE SEQUENCE gravport.raster_tiles_tile_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: raster_tiles_tile_id_seq; Type: SEQUENCE OWNED BY; Schema: gravport; Owner: -
--

ALTER SEQUENCE gravport.raster_tiles_tile_id_seq OWNED BY gravport.raster_tiles.tile_id;


--
-- Name: staging_gravity_points; Type: TABLE; Schema: gravport; Owner: -
--

CREATE TABLE gravport.staging_gravity_points (
    staged_point_id bigint NOT NULL,
    organization_id bigint NOT NULL,
    point_value double precision NOT NULL,
    point_anom_type character varying(10) NOT NULL,
    point_obs_type character varying(20) DEFAULT 'terrestrial'::character varying NOT NULL,
    data_level smallint DEFAULT 1 NOT NULL,
    point_metadata jsonb,
    source_file text,
    geom public.geometry(Point,4326) NOT NULL,
    staged_by bigint NOT NULL,
    staged_at timestamp with time zone DEFAULT now() NOT NULL,
    review_status character varying(20) DEFAULT 'pending'::character varying NOT NULL,
    reviewed_by bigint,
    reviewed_at timestamp with time zone,
    review_notes text,
    CONSTRAINT staging_gravity_points_data_level_check CHECK ((data_level = ANY (ARRAY[1, 2]))),
    CONSTRAINT staging_gravity_points_point_anom_type_check CHECK (((point_anom_type)::text = ANY ((ARRAY['FAA'::character varying, 'CBA'::character varying, 'SBA'::character varying, 'BA'::character varying, 'RAW'::character varying])::text[]))),
    CONSTRAINT staging_gravity_points_point_obs_type_check CHECK (((point_obs_type)::text = ANY ((ARRAY['airborne'::character varying, 'terrestrial'::character varying, 'satellite'::character varying])::text[]))),
    CONSTRAINT staging_gravity_points_review_status_check CHECK (((review_status)::text = ANY ((ARRAY['pending'::character varying, 'approved'::character varying, 'rejected'::character varying])::text[])))
);


--
-- Name: staging_gravity_points_staged_point_id_seq; Type: SEQUENCE; Schema: gravport; Owner: -
--

CREATE SEQUENCE gravport.staging_gravity_points_staged_point_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: staging_gravity_points_staged_point_id_seq; Type: SEQUENCE OWNED BY; Schema: gravport; Owner: -
--

ALTER SEQUENCE gravport.staging_gravity_points_staged_point_id_seq OWNED BY gravport.staging_gravity_points.staged_point_id;


--
-- Name: staging_gravity_rasters; Type: TABLE; Schema: gravport; Owner: -
--

CREATE TABLE gravport.staging_gravity_rasters (
    staged_raster_id bigint NOT NULL,
    organization_id bigint NOT NULL,
    raster_anom_type character varying(10) NOT NULL,
    raster_resolution numeric,
    raster_metadata jsonb,
    raster_path text,
    data_level smallint DEFAULT 2 NOT NULL,
    source_file text,
    staged_by bigint NOT NULL,
    staged_at timestamp with time zone DEFAULT now() NOT NULL,
    review_status character varying(20) DEFAULT 'pending'::character varying NOT NULL,
    reviewed_by bigint,
    reviewed_at timestamp with time zone,
    review_notes text,
    CONSTRAINT staging_gravity_rasters_data_level_check CHECK ((data_level = ANY (ARRAY[1, 2]))),
    CONSTRAINT staging_gravity_rasters_raster_anom_type_check CHECK (((raster_anom_type)::text = ANY ((ARRAY['FAA'::character varying, 'CBA'::character varying, 'SBA'::character varying, 'BA'::character varying])::text[]))),
    CONSTRAINT staging_gravity_rasters_review_status_check CHECK (((review_status)::text = ANY ((ARRAY['pending'::character varying, 'approved'::character varying, 'rejected'::character varying])::text[])))
);


--
-- Name: staging_gravity_rasters_staged_raster_id_seq; Type: SEQUENCE; Schema: gravport; Owner: -
--

CREATE SEQUENCE gravport.staging_gravity_rasters_staged_raster_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: staging_gravity_rasters_staged_raster_id_seq; Type: SEQUENCE OWNED BY; Schema: gravport; Owner: -
--

ALTER SEQUENCE gravport.staging_gravity_rasters_staged_raster_id_seq OWNED BY gravport.staging_gravity_rasters.staged_raster_id;


--
-- Name: users; Type: TABLE; Schema: gravport; Owner: -
--

CREATE TABLE gravport.users (
    user_id bigint NOT NULL,
    organization_id bigint,
    user_name character varying(80) NOT NULL,
    user_email character varying(160) NOT NULL,
    password_hash text NOT NULL,
    role character varying(20) DEFAULT 'user'::character varying NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    date_created timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone,
    CONSTRAINT users_role_check CHECK (((role)::text = ANY ((ARRAY['superadmin'::character varying, 'admin'::character varying, 'user'::character varying])::text[])))
);


--
-- Name: users_user_id_seq; Type: SEQUENCE; Schema: gravport; Owner: -
--

CREATE SEQUENCE gravport.users_user_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_user_id_seq; Type: SEQUENCE OWNED BY; Schema: gravport; Owner: -
--

ALTER SEQUENCE gravport.users_user_id_seq OWNED BY gravport.users.user_id;


--
-- Name: anomaly_gravity_point_data point_id; Type: DEFAULT; Schema: gravport; Owner: -
--

ALTER TABLE ONLY gravport.anomaly_gravity_point_data ALTER COLUMN point_id SET DEFAULT nextval('gravport.anomaly_gravity_point_data_point_id_seq'::regclass);


--
-- Name: anomaly_gravity_raster_data raster_id; Type: DEFAULT; Schema: gravport; Owner: -
--

ALTER TABLE ONLY gravport.anomaly_gravity_raster_data ALTER COLUMN raster_id SET DEFAULT nextval('gravport.anomaly_gravity_raster_data_raster_id_seq'::regclass);


--
-- Name: land_administrative_areas adm_id; Type: DEFAULT; Schema: gravport; Owner: -
--

ALTER TABLE ONLY gravport.land_administrative_areas ALTER COLUMN adm_id SET DEFAULT nextval('gravport.land_administrative_areas_adm_id_seq'::regclass);


--
-- Name: organizations organization_id; Type: DEFAULT; Schema: gravport; Owner: -
--

ALTER TABLE ONLY gravport.organizations ALTER COLUMN organization_id SET DEFAULT nextval('gravport.organizations_organization_id_seq'::regclass);


--
-- Name: raster_tiles tile_id; Type: DEFAULT; Schema: gravport; Owner: -
--

ALTER TABLE ONLY gravport.raster_tiles ALTER COLUMN tile_id SET DEFAULT nextval('gravport.raster_tiles_tile_id_seq'::regclass);


--
-- Name: staging_gravity_points staged_point_id; Type: DEFAULT; Schema: gravport; Owner: -
--

ALTER TABLE ONLY gravport.staging_gravity_points ALTER COLUMN staged_point_id SET DEFAULT nextval('gravport.staging_gravity_points_staged_point_id_seq'::regclass);


--
-- Name: staging_gravity_rasters staged_raster_id; Type: DEFAULT; Schema: gravport; Owner: -
--

ALTER TABLE ONLY gravport.staging_gravity_rasters ALTER COLUMN staged_raster_id SET DEFAULT nextval('gravport.staging_gravity_rasters_staged_raster_id_seq'::regclass);


--
-- Name: users user_id; Type: DEFAULT; Schema: gravport; Owner: -
--

ALTER TABLE ONLY gravport.users ALTER COLUMN user_id SET DEFAULT nextval('gravport.users_user_id_seq'::regclass);


--
-- Name: anomaly_gravity_point_data anomaly_gravity_point_data_pkey; Type: CONSTRAINT; Schema: gravport; Owner: -
--

ALTER TABLE ONLY gravport.anomaly_gravity_point_data
    ADD CONSTRAINT anomaly_gravity_point_data_pkey PRIMARY KEY (point_id);


--
-- Name: anomaly_gravity_raster_data anomaly_gravity_raster_data_pkey; Type: CONSTRAINT; Schema: gravport; Owner: -
--

ALTER TABLE ONLY gravport.anomaly_gravity_raster_data
    ADD CONSTRAINT anomaly_gravity_raster_data_pkey PRIMARY KEY (raster_id);


--
-- Name: land_administrative_areas land_administrative_areas_pkey; Type: CONSTRAINT; Schema: gravport; Owner: -
--

ALTER TABLE ONLY gravport.land_administrative_areas
    ADD CONSTRAINT land_administrative_areas_pkey PRIMARY KEY (adm_id);


--
-- Name: organizations organizations_organization_email_key; Type: CONSTRAINT; Schema: gravport; Owner: -
--

ALTER TABLE ONLY gravport.organizations
    ADD CONSTRAINT organizations_organization_email_key UNIQUE (organization_email);


--
-- Name: organizations organizations_pkey; Type: CONSTRAINT; Schema: gravport; Owner: -
--

ALTER TABLE ONLY gravport.organizations
    ADD CONSTRAINT organizations_pkey PRIMARY KEY (organization_id);


--
-- Name: point_administrative_areas point_administrative_areas_pkey; Type: CONSTRAINT; Schema: gravport; Owner: -
--

ALTER TABLE ONLY gravport.point_administrative_areas
    ADD CONSTRAINT point_administrative_areas_pkey PRIMARY KEY (point_id, adm_id);


--
-- Name: raster_administrative_areas raster_administrative_areas_pkey; Type: CONSTRAINT; Schema: gravport; Owner: -
--

ALTER TABLE ONLY gravport.raster_administrative_areas
    ADD CONSTRAINT raster_administrative_areas_pkey PRIMARY KEY (raster_id, adm_id);


--
-- Name: raster_tiles raster_tiles_pkey; Type: CONSTRAINT; Schema: gravport; Owner: -
--

ALTER TABLE ONLY gravport.raster_tiles
    ADD CONSTRAINT raster_tiles_pkey PRIMARY KEY (tile_id);


--
-- Name: staging_gravity_points staging_gravity_points_pkey; Type: CONSTRAINT; Schema: gravport; Owner: -
--

ALTER TABLE ONLY gravport.staging_gravity_points
    ADD CONSTRAINT staging_gravity_points_pkey PRIMARY KEY (staged_point_id);


--
-- Name: staging_gravity_rasters staging_gravity_rasters_pkey; Type: CONSTRAINT; Schema: gravport; Owner: -
--

ALTER TABLE ONLY gravport.staging_gravity_rasters
    ADD CONSTRAINT staging_gravity_rasters_pkey PRIMARY KEY (staged_raster_id);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: gravport; Owner: -
--

ALTER TABLE ONLY gravport.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (user_id);


--
-- Name: users users_user_email_key; Type: CONSTRAINT; Schema: gravport; Owner: -
--

ALTER TABLE ONLY gravport.users
    ADD CONSTRAINT users_user_email_key UNIQUE (user_email);


--
-- Name: anomaly_gravity_point_data_data_level_idx; Type: INDEX; Schema: gravport; Owner: -
--

CREATE INDEX anomaly_gravity_point_data_data_level_idx ON gravport.anomaly_gravity_point_data USING btree (data_level);


--
-- Name: anomaly_gravity_point_data_geom_idx; Type: INDEX; Schema: gravport; Owner: -
--

CREATE INDEX anomaly_gravity_point_data_geom_idx ON gravport.anomaly_gravity_point_data USING gist (geom);


--
-- Name: anomaly_gravity_point_data_organization_id_idx; Type: INDEX; Schema: gravport; Owner: -
--

CREATE INDEX anomaly_gravity_point_data_organization_id_idx ON gravport.anomaly_gravity_point_data USING btree (organization_id);


--
-- Name: anomaly_gravity_point_data_point_anom_type_idx; Type: INDEX; Schema: gravport; Owner: -
--

CREATE INDEX anomaly_gravity_point_data_point_anom_type_idx ON gravport.anomaly_gravity_point_data USING btree (point_anom_type);


--
-- Name: anomaly_gravity_point_data_status_idx; Type: INDEX; Schema: gravport; Owner: -
--

CREATE INDEX anomaly_gravity_point_data_status_idx ON gravport.anomaly_gravity_point_data USING btree (status);


--
-- Name: anomaly_gravity_raster_data_organization_id_idx; Type: INDEX; Schema: gravport; Owner: -
--

CREATE INDEX anomaly_gravity_raster_data_organization_id_idx ON gravport.anomaly_gravity_raster_data USING btree (organization_id);


--
-- Name: anomaly_gravity_raster_data_raster_anom_type_idx; Type: INDEX; Schema: gravport; Owner: -
--

CREATE INDEX anomaly_gravity_raster_data_raster_anom_type_idx ON gravport.anomaly_gravity_raster_data USING btree (raster_anom_type);


--
-- Name: land_administrative_areas_adm_level_idx; Type: INDEX; Schema: gravport; Owner: -
--

CREATE INDEX land_administrative_areas_adm_level_idx ON gravport.land_administrative_areas USING btree (adm_level);


--
-- Name: land_administrative_areas_geom_idx; Type: INDEX; Schema: gravport; Owner: -
--

CREATE INDEX land_administrative_areas_geom_idx ON gravport.land_administrative_areas USING gist (geom);


--
-- Name: raster_tiles_grid_geom_idx; Type: INDEX; Schema: gravport; Owner: -
--

CREATE INDEX raster_tiles_grid_geom_idx ON gravport.raster_tiles USING gist (grid_geom);


--
-- Name: raster_tiles_raster_id_idx; Type: INDEX; Schema: gravport; Owner: -
--

CREATE INDEX raster_tiles_raster_id_idx ON gravport.raster_tiles USING btree (raster_id);


--
-- Name: staging_gravity_points_geom_idx; Type: INDEX; Schema: gravport; Owner: -
--

CREATE INDEX staging_gravity_points_geom_idx ON gravport.staging_gravity_points USING gist (geom);


--
-- Name: staging_gravity_points_review_status_idx; Type: INDEX; Schema: gravport; Owner: -
--

CREATE INDEX staging_gravity_points_review_status_idx ON gravport.staging_gravity_points USING btree (review_status);


--
-- Name: staging_gravity_points_staged_by_idx; Type: INDEX; Schema: gravport; Owner: -
--

CREATE INDEX staging_gravity_points_staged_by_idx ON gravport.staging_gravity_points USING btree (staged_by);


--
-- Name: staging_gravity_rasters_review_status_idx; Type: INDEX; Schema: gravport; Owner: -
--

CREATE INDEX staging_gravity_rasters_review_status_idx ON gravport.staging_gravity_rasters USING btree (review_status);


--
-- Name: staging_gravity_rasters_staged_by_idx; Type: INDEX; Schema: gravport; Owner: -
--

CREATE INDEX staging_gravity_rasters_staged_by_idx ON gravport.staging_gravity_rasters USING btree (staged_by);


--
-- Name: anomaly_gravity_point_data anomaly_gravity_point_data_created_by_fkey; Type: FK CONSTRAINT; Schema: gravport; Owner: -
--

ALTER TABLE ONLY gravport.anomaly_gravity_point_data
    ADD CONSTRAINT anomaly_gravity_point_data_created_by_fkey FOREIGN KEY (created_by) REFERENCES gravport.users(user_id) ON DELETE SET NULL;


--
-- Name: anomaly_gravity_point_data anomaly_gravity_point_data_organization_id_fkey; Type: FK CONSTRAINT; Schema: gravport; Owner: -
--

ALTER TABLE ONLY gravport.anomaly_gravity_point_data
    ADD CONSTRAINT anomaly_gravity_point_data_organization_id_fkey FOREIGN KEY (organization_id) REFERENCES gravport.organizations(organization_id) ON DELETE RESTRICT;


--
-- Name: anomaly_gravity_raster_data anomaly_gravity_raster_data_created_by_fkey; Type: FK CONSTRAINT; Schema: gravport; Owner: -
--

ALTER TABLE ONLY gravport.anomaly_gravity_raster_data
    ADD CONSTRAINT anomaly_gravity_raster_data_created_by_fkey FOREIGN KEY (created_by) REFERENCES gravport.users(user_id) ON DELETE SET NULL;


--
-- Name: anomaly_gravity_raster_data anomaly_gravity_raster_data_organization_id_fkey; Type: FK CONSTRAINT; Schema: gravport; Owner: -
--

ALTER TABLE ONLY gravport.anomaly_gravity_raster_data
    ADD CONSTRAINT anomaly_gravity_raster_data_organization_id_fkey FOREIGN KEY (organization_id) REFERENCES gravport.organizations(organization_id) ON DELETE RESTRICT;


--
-- Name: point_administrative_areas point_administrative_areas_adm_id_fkey; Type: FK CONSTRAINT; Schema: gravport; Owner: -
--

ALTER TABLE ONLY gravport.point_administrative_areas
    ADD CONSTRAINT point_administrative_areas_adm_id_fkey FOREIGN KEY (adm_id) REFERENCES gravport.land_administrative_areas(adm_id) ON DELETE CASCADE;


--
-- Name: point_administrative_areas point_administrative_areas_point_id_fkey; Type: FK CONSTRAINT; Schema: gravport; Owner: -
--

ALTER TABLE ONLY gravport.point_administrative_areas
    ADD CONSTRAINT point_administrative_areas_point_id_fkey FOREIGN KEY (point_id) REFERENCES gravport.anomaly_gravity_point_data(point_id) ON DELETE CASCADE;


--
-- Name: raster_administrative_areas raster_administrative_areas_adm_id_fkey; Type: FK CONSTRAINT; Schema: gravport; Owner: -
--

ALTER TABLE ONLY gravport.raster_administrative_areas
    ADD CONSTRAINT raster_administrative_areas_adm_id_fkey FOREIGN KEY (adm_id) REFERENCES gravport.land_administrative_areas(adm_id) ON DELETE CASCADE;


--
-- Name: raster_administrative_areas raster_administrative_areas_raster_id_fkey; Type: FK CONSTRAINT; Schema: gravport; Owner: -
--

ALTER TABLE ONLY gravport.raster_administrative_areas
    ADD CONSTRAINT raster_administrative_areas_raster_id_fkey FOREIGN KEY (raster_id) REFERENCES gravport.anomaly_gravity_raster_data(raster_id) ON DELETE CASCADE;


--
-- Name: raster_tiles raster_tiles_raster_id_fkey; Type: FK CONSTRAINT; Schema: gravport; Owner: -
--

ALTER TABLE ONLY gravport.raster_tiles
    ADD CONSTRAINT raster_tiles_raster_id_fkey FOREIGN KEY (raster_id) REFERENCES gravport.anomaly_gravity_raster_data(raster_id) ON DELETE CASCADE;


--
-- Name: staging_gravity_points staging_gravity_points_organization_id_fkey; Type: FK CONSTRAINT; Schema: gravport; Owner: -
--

ALTER TABLE ONLY gravport.staging_gravity_points
    ADD CONSTRAINT staging_gravity_points_organization_id_fkey FOREIGN KEY (organization_id) REFERENCES gravport.organizations(organization_id) ON DELETE RESTRICT;


--
-- Name: staging_gravity_points staging_gravity_points_reviewed_by_fkey; Type: FK CONSTRAINT; Schema: gravport; Owner: -
--

ALTER TABLE ONLY gravport.staging_gravity_points
    ADD CONSTRAINT staging_gravity_points_reviewed_by_fkey FOREIGN KEY (reviewed_by) REFERENCES gravport.users(user_id) ON DELETE SET NULL;


--
-- Name: staging_gravity_points staging_gravity_points_staged_by_fkey; Type: FK CONSTRAINT; Schema: gravport; Owner: -
--

ALTER TABLE ONLY gravport.staging_gravity_points
    ADD CONSTRAINT staging_gravity_points_staged_by_fkey FOREIGN KEY (staged_by) REFERENCES gravport.users(user_id) ON DELETE RESTRICT;


--
-- Name: staging_gravity_rasters staging_gravity_rasters_organization_id_fkey; Type: FK CONSTRAINT; Schema: gravport; Owner: -
--

ALTER TABLE ONLY gravport.staging_gravity_rasters
    ADD CONSTRAINT staging_gravity_rasters_organization_id_fkey FOREIGN KEY (organization_id) REFERENCES gravport.organizations(organization_id) ON DELETE RESTRICT;


--
-- Name: staging_gravity_rasters staging_gravity_rasters_reviewed_by_fkey; Type: FK CONSTRAINT; Schema: gravport; Owner: -
--

ALTER TABLE ONLY gravport.staging_gravity_rasters
    ADD CONSTRAINT staging_gravity_rasters_reviewed_by_fkey FOREIGN KEY (reviewed_by) REFERENCES gravport.users(user_id) ON DELETE SET NULL;


--
-- Name: staging_gravity_rasters staging_gravity_rasters_staged_by_fkey; Type: FK CONSTRAINT; Schema: gravport; Owner: -
--

ALTER TABLE ONLY gravport.staging_gravity_rasters
    ADD CONSTRAINT staging_gravity_rasters_staged_by_fkey FOREIGN KEY (staged_by) REFERENCES gravport.users(user_id) ON DELETE RESTRICT;


--
-- Name: users users_organization_id_fkey; Type: FK CONSTRAINT; Schema: gravport; Owner: -
--

ALTER TABLE ONLY gravport.users
    ADD CONSTRAINT users_organization_id_fkey FOREIGN KEY (organization_id) REFERENCES gravport.organizations(organization_id) ON DELETE SET NULL;


--
-- PostgreSQL database dump complete
--

\unrestrict eouuGJJzdmxlgaXuLr5KDc8cnKwkPzna8GrSYDt6cNqfUwoFDnXwx1oj2EHfpm5

