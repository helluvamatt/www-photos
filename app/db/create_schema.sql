DROP TABLE IF EXISTS shareables;
DROP TABLE IF EXISTS photos;
DROP TABLE IF EXISTS albums;
DROP TABLE IF EXISTS profile_values;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS sessions;

-- CodeIgniter Session table
CREATE TABLE sessions (
	session_id CHARACTER VARYING(40) PRIMARY KEY DEFAULT '0',
	ip_address CHARACTER VARYING(45) DEFAULT '0' NOT null,
	user_agent CHARACTER VARYING(120) NOT null,
	last_activity NUMERIC(10) DEFAULT 0 NOT null,
	user_data TEXT NOT null
);
CREATE INDEX last_activity_idx ON sessions (last_activity);

DROP FUNCTION IF EXISTS save_session(p_session_id CHARACTER VARYING(40), p_ip_address CHARACTER VARYING(45), p_user_agent CHARACTER VARYING(120), p_last_activity NUMERIC(10), p_user_data TEXT);
CREATE OR REPLACE FUNCTION save_session(
		p_session_id CHARACTER VARYING(40),
		p_ip_address CHARACTER VARYING(45),
		p_user_agent CHARACTER VARYING(120),
		p_last_activity NUMERIC(10),
		p_user_data TEXT
) RETURNS VOID AS $$
BEGIN
	LOOP
        UPDATE sessions SET ip_address = p_ip_address, user_agent = p_user_agent, last_activity = p_last_activity, user_data = p_user_data WHERE session_id = p_session_id;
        IF found THEN
            RETURN;
        END IF;
        BEGIN
            INSERT INTO sessions (session_id, ip_address, user_agent, last_activity, user_data) VALUES (p_session_id, p_ip_address, p_user_agent, p_last_activity, p_user_data);
            RETURN;
        EXCEPTION WHEN unique_violation THEN
        
        END;
    END LOOP;
END;
$$ LANGUAGE plpgsql;

-- User table
CREATE TABLE users (
	id SERIAL PRIMARY KEY,
	username CHARACTER VARYING(64) NOT null UNIQUE,
	email CHARACTER VARYING(255) NOT null,
	realname_first CHARACTER VARYING(255) DEFAULT null,
	realname_last CHARACTER VARYING(255) DEFAULT null,
	password CHARACTER VARYING(255) NOT null,
	default_sharing SMALLINT NOT null DEFAULT 0, -- 0 => Private, 3 => Public
	profile_picture BIGINT DEFAULT NULL,
	created_at TIMESTAMP NOT null DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT null DEFAULT CURRENT_TIMESTAMP,
	deleted_at TIMESTAMP DEFAULT null
);

CREATE TABLE profile_values (
	id BIGSERIAL PRIMARY KEY,
	user_id INTEGER NOT null REFERENCES users(id),
	key CHARACTER VARYING(255) NOT null,
	value TEXT DEFAULT null,
	shared SMALLINT DEFAULT 1, -- 0 => Private, 1 => User DEFAULT, 2 => Custom Sharing, 3 => Public
	created_at TIMESTAMP NOT null DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT null DEFAULT CURRENT_TIMESTAMP,
	deleted_at TIMESTAMP DEFAULT null
);
CREATE UNIQUE INDEX idx_profile_values ON profile_values(user_id, key);

-- Photo Albums Table
CREATE TABLE albums (
	id BIGSERIAL PRIMARY KEY,
	title CHARACTER VARYING(255) NOT null,
	description TEXT DEFAULT null,
	shared SMALLINT DEFAULT 1, -- 0 => Private, 1 => User DEFAULT, 2 => Custom Sharing, 3 => Public
	created_at TIMESTAMP NOT null DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT null DEFAULT CURRENT_TIMESTAMP,
	owner_id BIGINT NOT null REFERENCES users(id),
	deleted_at TIMESTAMP DEFAULT null
);
CREATE INDEX idx_albums_owner ON albums(owner_id);

-- Photos Table
CREATE TABLE photos (
	id CHARACTER VARYING(255) PRIMARY KEY,
	title CHARACTER VARYING(255) DEFAULT null,
	description TEXT DEFAULT null,
	type CHARACTER VARYING(8) NOT null DEFAULT 'jpg',
	album_order INTEGER DEFAULT 0, -- Manual ordering of photos in albums
	shared SMALLINT DEFAULT 1, -- 0 => Private,  1 => Inherit from Album, 2 => Custom Sharing, 3 => Public
	created_at TIMESTAMP NOT null DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT null DEFAULT CURRENT_TIMESTAMP,
	album_id BIGINT NOT null REFERENCES albums(id),
	owner_id BIGINT NOT null REFERENCES users(id),
	deleted_at TIMESTAMP DEFAULT null
);
CREATE INDEX idx_photos_owner ON photos(owner_id);
CREATE INDEX idx_photos_album ON photos(album_id);

CREATE TABLE shareables (
	user_id INTEGER NOT null REFERENCES users(id),
	shareable_id BIGINT NOT null,
	shareable_type CHARACTER VARYING(64)
);
CREATE INDEX idx_shareables ON shareables(user_id, shareable_type, shareable_id);
