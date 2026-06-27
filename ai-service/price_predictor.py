import numpy as np
from sklearn.ensemble import RandomForestRegressor
import pandas as pd

class PricePredictor:
    def __init__(self):
        self.model = RandomForestRegressor(n_estimators=100, random_state=42)
        self.is_trained = False
        
    def train_model(self, historical_data):
        # This would be trained on actual data
        # For demo, we'll use dummy training
        X = np.random.rand(100, 5)
        y = np.random.randint(20000, 100000, 100)
        self.model.fit(X, y)
        self.is_trained = True
        
    def predict_price(self, property_features):
        # City price mapping (simplified)
        city_prices = {
            'Islamabad': 60000,
            'Lahore': 45000,
            'Karachi': 50000,
            'Rawalpindi': 40000,
            'Peshawar': 35000
        }
        
        # Base price from city
        base_price = city_prices.get(property_features['city'], 40000)
        
        # Adjustments
        bedroom_adjustment = property_features['bedrooms'] * 15000
        bathroom_adjustment = property_features['bathrooms'] * 5000
        
        # Property type adjustment
        type_multipliers = {
            'apartment': 1,
            'house': 1.3,
            'studio': 0.8,
            'shared': 0.7
        }
        type_multiplier = type_multipliers.get(property_features['property_type'], 1)
        
        # Facilities adjustment
        facility_adjustment = len(property_features.get('facilities', [])) * 2000
        
        predicted_price = (base_price + bedroom_adjustment + bathroom_adjustment + facility_adjustment) * type_multiplier
        
        return round(predicted_price, -3)  # Round to nearest thousand