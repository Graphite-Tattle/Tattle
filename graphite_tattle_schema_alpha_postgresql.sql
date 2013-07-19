CREATE TABLE users (
  user_id serial,
  username varchar(100) NOT NULL,
  email varchar(200) NOT NULL,
  role varchar(100) NOT NULL DEFAULT 'user',
  password varchar(100) NOT NULL, -- COMMENT 'This hash is generated using fCryptography::hashPassword()'
  PRIMARY KEY (user_id),
  CONSTRAINT username UNIQUE(username),
  CONSTRAINT email UNIQUE (email)
);

CREATE TABLE settings (
  name varchar(100) NOT NULL,
  friendly_name varchar(200) NOT NULL,
  value varchar(500) NOT NULL,
  value_type varchar(100) NOT NULL DEFAULT 'string',
  plugin varchar(200) NOT NULL,
  type varchar(200) NOT NULL DEFAULT 'system', -- COMMENT 'So far this can be user or system'
  owner_id integer NOT NULL, -- COMMENT 'Can be used by plugins to associated the value with a user or other object like a dashboard'
  PRIMARY KEY (name, owner_id)
);

CREATE TABLE dashboards (
  dashboard_id serial,
  user_id integer NOT NULL,
  name varchar(255) NOT NULL,
  description varchar(1000) NOT NULL DEFAULT '',
  columns integer NOT NULL DEFAULT '2',
  background_color varchar(15) NOT NULL DEFAULT '000000',
  graph_height integer NOT NULL DEFAULT '300',
  graph_width integer NOT NULL DEFAULT '300',
  refresh_rate integer NOT NULL DEFAULT 0,
  PRIMARY KEY (dashboard_id),
  CONSTRAINT user_id UNIQUE(user_id, name)
);

CREATE TABLE checks (
  check_id serial,
  user_id integer NOT NULL,
  name varchar(255) NOT NULL,
  target varchar(1000) NOT NULL,
  error decimal(20,3) NOT NULL,
  warn decimal(20,3) NOT NULL,
  sample varchar(255) NOT NULL DEFAULT '10',
  baseline varchar(255) NOT NULL DEFAULT 'average',
  visibility integer NOT NULL DEFAULT '0',
  over_under integer NOT NULL DEFAULT '0',
  enabled varchar(45) NOT NULL DEFAULT '1',
  last_check_status integer DEFAULT NULL,
  last_check_value integer DEFAULT NULL,
  last_check_time timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  repeat_delay integer NOT NULL DEFAULT '60',
  type varchar(255) NOT NULL,
  regression_type varchar(255) DEFAULT NULL,
  number_of_regressions integer DEFAULT NULL,
  PRIMARY KEY (check_id),
  CONSTRAINT user_id_name UNIQUE(user_id, name)
);

CREATE TABLE check_results (
  result_id serial,
  check_id integer DEFAULT NULL,
  status integer DEFAULT NULL,
  value decimal(20,3) DEFAULT NULL,
  state integer DEFAULT NULL,
  timestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  acknowledged integer NOT NULL DEFAULT '0',
  PRIMARY KEY (result_id)
);
CREATE INDEX check_results_check_id ON check_results (check_id);

CREATE TABLE graphs (
  graph_id serial,
  name varchar(255) NOT NULL,
  area varchar(45) NOT NULL,
  vtitle varchar(45) DEFAULT NULL,
  description varchar(1000) NOT NULL DEFAULT '',
  dashboard_id integer NOT NULL,
  weight integer NOT NULL DEFAULT '0',
  time_value integer NOT NULL DEFAULT '2',
  unit varchar(10) NOT NULL DEFAULT 'hours',
  custom_opts varchar(1000) NULL,
  PRIMARY KEY (graph_id),
  CONSTRAINT dashboard_id UNIQUE(dashboard_id, name)
);

CREATE TABLE lines (
  line_id serial,
  color varchar(45) DEFAULT NULL,
  target varchar(1000) NOT NULL DEFAULT '',
  alias varchar(255) DEFAULT NULL,
  graph_id integer DEFAULT NULL,
  PRIMARY KEY (line_id)
);

CREATE TABLE subscriptions (
  subscription_id serial,
  check_id integer NOT NULL,
  user_id integer NOT NULL,
  threshold integer NOT NULL DEFAULT '0',
  method varchar(255) NOT NULL,
  status integer NOT NULL DEFAULT '0',
  frequency integer NOT NULL DEFAULT '0',
  PRIMARY KEY (subscription_id),
  CONSTRAINT user_method UNIQUE(check_id, user_id, method)
);
CREATE INDEX subscriptions_user_id ON subscriptions (user_id);

