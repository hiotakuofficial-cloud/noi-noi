-- HIOTAKU Database Schema
-- Complete table structure for authentication and live streaming features

-- Users table - Main authentication table
CREATE TABLE public.users (
  id uuid NOT NULL DEFAULT uuid_generate_v4(),
  email text NOT NULL UNIQUE,
  password text,
  avatar_id text,
  created_at timestamp without time zone DEFAULT now(),
  username text,
  CONSTRAINT users_pkey PRIMARY KEY (id)
);

-- OTP verification table
CREATE TABLE public.otps (
  email text NOT NULL,
  otp_hash text NOT NULL,
  created_at timestamp with time zone NOT NULL DEFAULT now(),
  expires_at timestamp with time zone NOT NULL,
  attempts integer NOT NULL DEFAULT 0,
  last_sent_at timestamp with time zone NOT NULL DEFAULT now(),
  used boolean NOT NULL DEFAULT false,
  CONSTRAINT otps_pkey PRIMARY KEY (email)
);

-- User favorites table
CREATE TABLE public.favorites (
  id uuid NOT NULL DEFAULT uuid_generate_v4(),
  user_id uuid,
  anime_id text NOT NULL,
  added_at timestamp without time zone DEFAULT now(),
  CONSTRAINT favorites_pkey PRIMARY KEY (id),
  CONSTRAINT favorites_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id)
);

-- Friends system tables
CREATE TABLE public.friends (
  id uuid NOT NULL DEFAULT uuid_generate_v4(),
  user_id uuid,
  friend_user_id uuid,
  created_at timestamp without time zone DEFAULT now(),
  CONSTRAINT friends_pkey PRIMARY KEY (id),
  CONSTRAINT friends_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id),
  CONSTRAINT friends_friend_user_id_fkey FOREIGN KEY (friend_user_id) REFERENCES public.users(id)
);

CREATE TABLE public.friend_requests (
  id uuid NOT NULL DEFAULT uuid_generate_v4(),
  sender_id uuid NOT NULL,
  receiver_id uuid NOT NULL,
  message text,
  status text NOT NULL DEFAULT 'pending'::text CHECK (status = ANY (ARRAY['pending'::text, 'accepted'::text, 'rejected'::text])),
  created_at timestamp without time zone DEFAULT now(),
  updated_at timestamp without time zone DEFAULT now(),
  CONSTRAINT friend_requests_pkey PRIMARY KEY (id),
  CONSTRAINT friend_requests_sender_fkey FOREIGN KEY (sender_id) REFERENCES public.users(id),
  CONSTRAINT friend_requests_receiver_fkey FOREIGN KEY (receiver_id) REFERENCES public.users(id)
);

-- Public live streaming tables
CREATE TABLE public.public_lives (
  id uuid NOT NULL DEFAULT uuid_generate_v4(),
  anime_id text NOT NULL,
  host_user_id uuid,
  title text,
  created_at timestamp without time zone DEFAULT now(),
  room_code text UNIQUE,
  current_state text DEFAULT 'waiting'::text CHECK (current_state = ANY (ARRAY['waiting'::text, 'watching'::text, 'paused'::text, 'ended'::text])),
  video_timestamp bigint DEFAULT 0,
  CONSTRAINT public_lives_pkey PRIMARY KEY (id),
  CONSTRAINT public_lives_host_user_id_fkey FOREIGN KEY (host_user_id) REFERENCES public.users(id)
);

CREATE TABLE public.public_live_viewers (
  id uuid NOT NULL DEFAULT uuid_generate_v4(),
  live_id uuid,
  viewer_user_id uuid,
  joined_at timestamp without time zone DEFAULT now(),
  CONSTRAINT public_live_viewers_pkey PRIMARY KEY (id),
  CONSTRAINT public_live_viewers_live_id_fkey FOREIGN KEY (live_id) REFERENCES public.public_lives(id),
  CONSTRAINT public_live_viewers_viewer_user_id_fkey FOREIGN KEY (viewer_user_id) REFERENCES public.users(id)
);

CREATE TABLE public.public_live_chats (
  id uuid NOT NULL DEFAULT uuid_generate_v4(),
  live_id uuid,
  sender_user_id uuid,
  message text NOT NULL,
  sent_at timestamp without time zone DEFAULT now(),
  CONSTRAINT public_live_chats_pkey PRIMARY KEY (id),
  CONSTRAINT public_live_chats_live_id_fkey FOREIGN KEY (live_id) REFERENCES public.public_lives(id),
  CONSTRAINT public_live_chats_sender_user_id_fkey FOREIGN KEY (sender_user_id) REFERENCES public.users(id)
);

-- Private live streaming tables
CREATE TABLE public.private_lives (
  id uuid NOT NULL DEFAULT uuid_generate_v4(),
  host_user_id uuid,
  password text NOT NULL,
  title text,
  created_at timestamp without time zone DEFAULT now(),
  room_code text UNIQUE,
  anime_id text,
  current_state text DEFAULT 'waiting'::text CHECK (current_state = ANY (ARRAY['waiting'::text, 'watching'::text, 'paused'::text, 'ended'::text])),
  video_timestamp bigint DEFAULT 0,
  CONSTRAINT private_lives_pkey PRIMARY KEY (id),
  CONSTRAINT private_lives_host_user_id_fkey FOREIGN KEY (host_user_id) REFERENCES public.users(id)
);

CREATE TABLE public.private_live_viewers (
  id uuid NOT NULL DEFAULT uuid_generate_v4(),
  live_id uuid,
  viewer_user_id uuid,
  joined_at timestamp without time zone DEFAULT now(),
  CONSTRAINT private_live_viewers_pkey PRIMARY KEY (id),
  CONSTRAINT private_live_viewers_live_id_fkey FOREIGN KEY (live_id) REFERENCES public.private_lives(id),
  CONSTRAINT private_live_viewers_viewer_user_id_fkey FOREIGN KEY (viewer_user_id) REFERENCES public.users(id)
);

CREATE TABLE public.private_live_chats (
  id uuid NOT NULL DEFAULT uuid_generate_v4(),
  live_id uuid,
  sender_user_id uuid,
  message text NOT NULL,
  sent_at timestamp without time zone DEFAULT now(),
  CONSTRAINT private_live_chats_pkey PRIMARY KEY (id),
  CONSTRAINT private_live_chats_live_id_fkey FOREIGN KEY (live_id) REFERENCES public.private_lives(id),
  CONSTRAINT private_live_chats_sender_user_id_fkey FOREIGN KEY (sender_user_id) REFERENCES public.users(id)
);

-- Invite system for private rooms
CREATE TABLE public.invite_requests (
  id uuid NOT NULL DEFAULT gen_random_uuid(),
  room_id uuid,
  sender_user_id uuid,
  receiver_user_id uuid,
  status text DEFAULT 'pending'::text CHECK (status = ANY (ARRAY['pending'::text, 'accepted'::text, 'declined'::text, 'expired'::text])),
  message text,
  created_at timestamp without time zone DEFAULT now(),
  expires_at timestamp without time zone DEFAULT (now() + '24:00:00'::interval),
  responded_at timestamp without time zone,
  CONSTRAINT invite_requests_pkey PRIMARY KEY (id),
  CONSTRAINT invite_requests_room_id_fkey FOREIGN KEY (room_id) REFERENCES public.private_lives(id),
  CONSTRAINT invite_requests_sender_user_id_fkey FOREIGN KEY (sender_user_id) REFERENCES public.users(id),
  CONSTRAINT invite_requests_receiver_user_id_fkey FOREIGN KEY (receiver_user_id) REFERENCES public.users(id)
);

-- Room management tables
CREATE TABLE public.room_states (
  id uuid NOT NULL DEFAULT uuid_generate_v4(),
  room_code text NOT NULL UNIQUE,
  room_type text NOT NULL CHECK (room_type = ANY (ARRAY['public'::text, 'private'::text])),
  current_state text NOT NULL DEFAULT 'waiting'::text CHECK (current_state = ANY (ARRAY['waiting'::text, 'watching'::text, 'paused'::text, 'ended'::text])),
  anime_id text,
  episode_id text,
  video_timestamp bigint DEFAULT 0,
  video_url text,
  host_user_id uuid,
  created_at timestamp without time zone DEFAULT now(),
  updated_at timestamp without time zone DEFAULT now(),
  CONSTRAINT room_states_pkey PRIMARY KEY (id),
  CONSTRAINT room_states_host_user_id_fkey FOREIGN KEY (host_user_id) REFERENCES public.users(id)
);

CREATE TABLE public.room_participants (
  id uuid NOT NULL DEFAULT uuid_generate_v4(),
  room_code text NOT NULL,
  user_id uuid NOT NULL,
  role text NOT NULL DEFAULT 'viewer'::text CHECK (role = ANY (ARRAY['host'::text, 'viewer'::text])),
  joined_at timestamp without time zone DEFAULT now(),
  last_seen timestamp without time zone DEFAULT now(),
  is_active boolean DEFAULT true,
  CONSTRAINT room_participants_pkey PRIMARY KEY (id),
  CONSTRAINT room_participants_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id)
);

CREATE TABLE public.room_events (
  id uuid NOT NULL DEFAULT uuid_generate_v4(),
  room_code text NOT NULL,
  event_type text NOT NULL CHECK (event_type = ANY (ARRAY['session_start'::text, 'video_play'::text, 'video_pause'::text, 'video_seek'::text, 'user_join'::text, 'user_leave'::text, 'episode_change'::text])),
  event_data jsonb,
  sender_user_id uuid,
  created_at timestamp without time zone DEFAULT now(),
  CONSTRAINT room_events_pkey PRIMARY KEY (id),
  CONSTRAINT room_events_sender_user_id_fkey FOREIGN KEY (sender_user_id) REFERENCES public.users(id)
);

-- Indexes for better performance
CREATE INDEX idx_users_email ON public.users(email);
CREATE INDEX idx_favorites_user_id ON public.favorites(user_id);
CREATE INDEX idx_friends_user_id ON public.friends(user_id);
CREATE INDEX idx_room_states_room_code ON public.room_states(room_code);
CREATE INDEX idx_room_participants_room_code ON public.room_participants(room_code);
CREATE INDEX idx_room_events_room_code ON public.room_events(room_code);
