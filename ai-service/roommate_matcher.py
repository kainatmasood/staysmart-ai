class RoommateMatcher:
    def __init__(self):
        pass
        
    def find_matches(self, current_user, potential_matches):
        matches = []
        
        for potential in potential_matches:
            match_score = 0
            
            # Budget compatibility (30%)
            current_budget_min = float(current_user.get('budget_min', 0))
            current_budget_max = float(current_user.get('budget_max', 100000))
            potential_budget_min = float(potential.get('budget_min', 0))
            potential_budget_max = float(potential.get('budget_max', 100000))
            
            budget_overlap = max(0, min(current_budget_max, potential_budget_max) - 
                                max(current_budget_min, potential_budget_min))
            if budget_overlap > 0:
                match_score += 0.3
                
            # Lifestyle match (25%)
            if current_user.get('lifestyle') == potential.get('lifestyle'):
                match_score += 0.25
                
            # Location match (20%)
            if current_user.get('preferred_location') and potential.get('preferred_location'):
                if current_user['preferred_location'].lower() == potential['preferred_location'].lower():
                    match_score += 0.20
                    
            # Occupation match (15%)
            if current_user.get('occupation') and potential.get('occupation'):
                if current_user['occupation'] == potential['occupation']:
                    match_score += 0.15
                    
            # Gender preference (10%)
            if current_user.get('gender_preference') and potential.get('gender_preference'):
                if current_user['gender_preference'] == 'any' or potential['gender_preference'] == 'any':
                    match_score += 0.05
                elif current_user['gender_preference'] == potential.get('gender', ''):
                    match_score += 0.10
                    
            if match_score > 0.4:  # Only return matches above 40%
                matches.append({
                    'user_id': potential['user_id'],
                    'match_score': round(match_score * 100, 2),
                    'budget': potential.get('budget_max', 0),
                    'lifestyle': potential.get('lifestyle'),
                    'occupation': potential.get('occupation'),
                    'location': potential.get('preferred_location')
                })
                
        # Sort by match score
        matches.sort(key=lambda x: x['match_score'], reverse=True)
        
        return matches[:5]  # Return top 5 matches