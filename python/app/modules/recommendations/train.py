#!/usr/bin/env python3
"""
Periodic training script for the recommendation model.
This script connects to the database, extracts user-item interactions,
trains a collaborative filtering model using Surprise, and saves it.
Run as a cron job or Kubernetes CronJob.
"""

import os
import sys
import pickle
import pandas as pd
from sqlalchemy import create_engine, text
from surprise import Dataset, Reader, SVD

def main():
    return _main_impl()
def _main_impl():
    db_url = os.getenv("DATABASE_URL")
    if not db_url:
        print("DATABASE_URL not set")
        sys.exit(1)

    engine = create_engine(db_url)
    # Query interactions from analytics_events
    query = """
        SELECT user_id, properties->>'item_id' as item_id, 
               CASE event_type
                 WHEN 'purchase' THEN 5
                 WHEN 'cart_add' THEN 3
                 ELSE 1
               END as weight
        FROM python.analytics_events
        WHERE event_type IN ('product_view', 'cart_add', 'purchase')
          AND properties ? 'item_id'
    """
    df = pd.read_sql(query, engine)
    if df.empty:
        print("No interaction data")
        sys.exit(0)

    # Aggregate weights per user-item
    df = df.groupby(['user_id', 'item_id'], as_index=False)['weight'].sum()
    df.rename(columns={'user_id':'userID', 'item_id':'itemID', 'weight':'rating'}, inplace=True)

    reader = Reader(rating_scale=(1, df['rating'].max()))
    data = Dataset.load_from_df(df[['userID','itemID','rating']], reader)
    trainset = data.build_full_trainset()
    model = SVD(n_factors=50, n_epochs=20, random_state=42)
    model.fit(trainset)

    # Save model
    model_dir = 'models'
    os.makedirs(model_dir, exist_ok=True)
    model_path = os.path.join(model_dir, 'recommendation.pkl')
    with open(model_path, 'wb') as f:
        pickle.dump(model, f)
    print(f"Model trained and saved to {model_path}")

if __name__ == "__main__":
    main()
