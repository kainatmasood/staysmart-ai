import pandas as pd
import numpy as np
from sklearn.preprocessing import MinMaxScaler
from sklearn.metrics.pairwise import cosine_similarity

class PropertyRecommender:
    def __init__(self):
        self.scaler = MinMaxScaler()
        
    def get_recommendations(self, user_prefs, properties):
        if not properties:
            return []
            
        recommendations = []
        
        for property in properties:
            match_score = 0
            
            # Budget compatibility (30% weight)
            property_price = float(property['rent_monthly'])
            user_budget = float(user_prefs['budget'])
            if property_price <= user_budget:
                budget_score = 1 - (property_price / user_budget)
                match_score += budget_score * 0.3
            else:
                budget_score = max(0, 1 - (property_price - user_budget) / user_budget)
                match_score += budget_score * 0.2
                
            # Location match (20% weight)
            if user_prefs['city'] and user_prefs['city'].lower() in property['city'].lower():
                match_score += 0.2
                
            # Property type match (15% weight)
            if user_prefs['property_type'] and user_prefs['property_type'] == property['property_type']:
                match_score += 0.15
                
            # Bedrooms match (15% weight)
            if property['bedrooms'] >= user_prefs['bedrooms']:
                match_score += 0.15
            else:
                match_score += (property['bedrooms'] / user_prefs['bedrooms']) * 0.1
                
            # Facilities match (20% weight)
            if property['facilities']:
                facilities_list = property['facilities'].split(',')
                # Assume user prefers certain facilities
                preferred_facilities = ['wifi', 'ac', 'parking', 'security']
                facility_score = sum(1 for f in preferred_facilities if f in str(facilities_list).lower()) / len(preferred_facilities)
                match_score += facility_score * 0.2
                
            # Calculate compatibility percentage
            compatibility = round(match_score * 100, 2)
            
            recommendations.append({
                'property_id': property['property_id'],
                'property_name': property['property_name'],
                'address': property['address'],
                'rent_monthly': property['rent_monthly'],
                'bedrooms': property['bedrooms'],
                'bathrooms': property['bathrooms'],
                'match_score': compatibility,
                'match_level': self.get_match_level(compatibility)
            })
            
        # Sort by match score
        recommendations.sort(key=lambda x: x['match_score'], reverse=True)
        
        return recommendations[:10]  # Return top 10
    
    def get_match_level(self, score):
        if score >= 90:
            return "Excellent Match"
        elif score >= 75:
            return "Very Good Match"
        elif score >= 60:
            return "Good Match"
        elif score >= 45:
            return "Potential Match"
        else:
            return "Low Match"