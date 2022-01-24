
DROP SEQUENCE IF EXISTS prayer_time_id_seq;
CREATE SEQUENCE prayer_time_id_seq INCREMENT 1 MINVALUE 1 MAXVALUE 2147483647 CACHE 1;

DROP TABLE IF EXISTS "prayer_time";
CREATE TABLE "public"."prayer_time" (
    "id" integer DEFAULT nextval('prayer_time_id_seq') NOT NULL,
    "trigger_identity_1" character(40) DEFAULT '' NOT NULL,
    "next_notification_1" integer DEFAULT '0' NOT NULL,
    "trigger_identity_2" character(40) DEFAULT '' NOT NULL,
    "next_notification_2" integer DEFAULT '0' NOT NULL,
    "trigger_identity_3" character(40) DEFAULT '' NOT NULL,
    "next_notification_3" integer DEFAULT '0' NOT NULL,
    "trigger_identity_4" character(40) DEFAULT '' NOT NULL,
    "next_notification_4" integer DEFAULT '0' NOT NULL,
    "trigger_identity_5" character(40) DEFAULT '' NOT NULL,
    "next_notification_5" integer DEFAULT '0' NOT NULL,
    "trigger_identity_6" character(40) DEFAULT '' NOT NULL,
    "next_notification_6" integer DEFAULT '0' NOT NULL,
    "trigger_identity_7" character(40) DEFAULT '' NOT NULL,
    "next_notification_7" integer DEFAULT '0' NOT NULL,
    "trigger_identity_8" character(40) DEFAULT '' NOT NULL,
    "next_notification_8" integer DEFAULT '0' NOT NULL,
    "trigger_identity_9" character(40) DEFAULT '' NOT NULL,
    "next_notification_9" integer DEFAULT '0' NOT NULL,
    "trigger_identity_10" character(40) DEFAULT '' NOT NULL,
    "next_notification_10" integer DEFAULT '0' NOT NULL,
    CONSTRAINT "prayer_time_pkey" PRIMARY KEY ("id")
) WITH (oids = false);

CREATE INDEX "prayer_time_next_notification_1" ON "public"."prayer_time" USING btree ("next_notification_1");
CREATE INDEX "prayer_time_next_notification_2" ON "public"."prayer_time" USING btree ("next_notification_2");
CREATE INDEX "prayer_time_next_notification_3" ON "public"."prayer_time" USING btree ("next_notification_3");
CREATE INDEX "prayer_time_next_notification_4" ON "public"."prayer_time" USING btree ("next_notification_4");
CREATE INDEX "prayer_time_next_notification_5" ON "public"."prayer_time" USING btree ("next_notification_5");
CREATE INDEX "prayer_time_next_notification_6" ON "public"."prayer_time" USING btree ("next_notification_6");
CREATE INDEX "prayer_time_next_notification_7" ON "public"."prayer_time" USING btree ("next_notification_7");
CREATE INDEX "prayer_time_next_notification_8" ON "public"."prayer_time" USING btree ("next_notification_8");
CREATE INDEX "prayer_time_next_notification_9" ON "public"."prayer_time" USING btree ("next_notification_9");
CREATE INDEX "prayer_time_next_notification_10" ON "public"."prayer_time" USING btree ("next_notification_10");
CREATE INDEX "prayer_time_trigger_identity_1" ON "public"."prayer_time" USING btree ("trigger_identity_1");
CREATE INDEX "prayer_time_trigger_identity_2" ON "public"."prayer_time" USING btree ("trigger_identity_2");
CREATE INDEX "prayer_time_trigger_identity_3" ON "public"."prayer_time" USING btree ("trigger_identity_3");
CREATE INDEX "prayer_time_trigger_identity_4" ON "public"."prayer_time" USING btree ("trigger_identity_4");
CREATE INDEX "prayer_time_trigger_identity_5" ON "public"."prayer_time" USING btree ("trigger_identity_5");
CREATE INDEX "prayer_time_trigger_identity_6" ON "public"."prayer_time" USING btree ("trigger_identity_6");
CREATE INDEX "prayer_time_trigger_identity_7" ON "public"."prayer_time" USING btree ("trigger_identity_7");
CREATE INDEX "prayer_time_trigger_identity_8" ON "public"."prayer_time" USING btree ("trigger_identity_8");
CREATE INDEX "prayer_time_trigger_identity_9" ON "public"."prayer_time" USING btree ("trigger_identity_9");
CREATE INDEX "prayer_time_trigger_identity_10" ON "public"."prayer_time" USING btree ("trigger_identity_10");

#DROP TABLE IF EXISTS "prayer_time_old";
#CREATE TABLE "public"."prayer_time_old" (
#    "trigger_identity" character(40) NOT NULL,
#    "next_notification" integer NOT NULL,
#    CONSTRAINT "prayer_time_trigger_identity" PRIMARY KEY ("trigger_identity")
#) WITH (oids = false);

#CREATE INDEX "prayer_time_next_notification" ON "public"."prayer_time_old" USING btree ("next_notification");
